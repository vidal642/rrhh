"""
Microservicio de Reconocimiento Facial — Sistema RRHH
FastAPI + DeepFace (Facenet512)
"""

import base64
import io
import logging
import os
from typing import List, Optional

import cv2
import numpy as np
from deepface import DeepFace
from fastapi import FastAPI, HTTPException, Security, Depends
from fastapi.middleware.cors import CORSMiddleware
from fastapi.security.api_key import APIKeyHeader
from PIL import Image
from pydantic import BaseModel

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
logger = logging.getLogger("face_service")

MODELO_FACIAL = os.getenv("MODELO_FACIAL", "Facenet512")
DETECTOR_BACKEND = os.getenv("DETECTOR_BACKEND", "retinaface")
UMBRAL_CONFIANZA = float(os.getenv("UMBRAL_CONFIANZA", "0.55"))

app = FastAPI(
    title="Microservicio de Reconocimiento Facial — RRHH",
    description="Genera y compara embeddings faciales usando DeepFace (Facenet512)",
    version="1.0.0",
)

API_KEY = os.getenv("API_KEY", "mi_llave_secreta_rrhh_2026")
api_key_header = APIKeyHeader(name="X-API-Key", auto_error=False)

async def verificar_api_key(api_key: str = Security(api_key_header)):
    if api_key != API_KEY:
        raise HTTPException(status_code=403, detail="Acceso denegado: API Key inválida o faltante")
    return api_key

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class SolicitudEmbedding(BaseModel):
    """Solicitud para generar embedding desde una o varias imágenes en base64."""
    imagenes: List[str]

class EmbeddingRegistrado(BaseModel):
    """Embedding registrado de un empleado."""
    id_empleado: int
    embedding: List[float]

class SolicitudComparacion(BaseModel):
    """Solicitud para comparar una imagen capturada contra embeddings registrados."""
    imagen: Optional[str] = None
    embedding: Optional[List[float]] = None
    embeddings_registrados: Optional[List[EmbeddingRegistrado]] = None

EMBEDDINGS_CACHE: List[EmbeddingRegistrado] = []

def base64_a_imagen_bgr(b64_str: str) -> np.ndarray:
    """Convierte string base64 (con o sin prefijo) a imagen OpenCV (BGR)."""
    if "," in b64_str:
        b64_str = b64_str.split(",", 1)[1]

    img_bytes = base64.b64decode(b64_str)
    img_pil = Image.open(io.BytesIO(img_bytes)).convert("RGB")
    img_bgr = cv2.cvtColor(np.array(img_pil), cv2.COLOR_RGB2BGR)
    return img_bgr


def obtener_embedding(img_bgr: np.ndarray) -> List[float]:
    """
    Extrae el vector de embedding facial de una imagen.
    Lanza ValueError si no detecta ningún rostro.
    """
    try:
        resultado = DeepFace.represent(
            img_path=img_bgr,
            model_name=MODELO_FACIAL,
            detector_backend=DETECTOR_BACKEND,
            enforce_detection=True,
            align=True,
        )
    except ValueError as e:
        # DeepFace arroja ValueError cuando enforce_detection=True y no halla rostro
        raise ValueError("No se detectó ningún rostro. Coloque su rostro frente a la cámara.")
        
    if not resultado:
        raise ValueError("No se detectó ningún rostro. Coloque su rostro frente a la cámara.")
        
    if len(resultado) > 1:
        raise ValueError("Se detectaron varios rostros. Asegúrese de que solo una persona esté frente a la cámara.")

    embedding = resultado[0]["embedding"]
    return embedding


def similitud_coseno(vec_a: List[float], vec_b: List[float]) -> float:
    """Calcula la similitud coseno entre dos vectores. Rango [0, 1]."""
    a = np.array(vec_a, dtype=np.float64)
    b = np.array(vec_b, dtype=np.float64)
    norma_a = np.linalg.norm(a)
    norma_b = np.linalg.norm(b)
    if norma_a == 0 or norma_b == 0:
        return 0.0
    return float(np.dot(a, b) / (norma_a * norma_b))


def embedding_promedio(embeddings: List[List[float]]) -> List[float]:
    """Calcula el promedio elemento a elemento de una lista de embeddings."""
    arr = np.array(embeddings, dtype=np.float64)
    return arr.mean(axis=0).tolist()

@app.get("/health", tags=["Sistema"])
def health_check():
    """Verificar que el servicio está activo."""
    return {
        "estado": "ok",
        "modelo": MODELO_FACIAL,
        "detector": DETECTOR_BACKEND,
        "embeddings_en_cache": len(EMBEDDINGS_CACHE)
    }

@app.post("/sincronizar-embeddings", tags=["Sistema"], dependencies=[Depends(verificar_api_key)])
def sincronizar_embeddings(embeddings: List[EmbeddingRegistrado]):
    """Actualiza la caché local de embeddings en memoria."""
    global EMBEDDINGS_CACHE
    EMBEDDINGS_CACHE = embeddings
    logger.info(f"Caché sincronizada: {len(EMBEDDINGS_CACHE)} embeddings cargados.")
    return {"status": "ok", "total": len(EMBEDDINGS_CACHE)}


@app.post("/generar-embedding", tags=["Reconocimiento"], dependencies=[Depends(verificar_api_key)])
def generar_embedding(solicitud: SolicitudEmbedding):
    """
    Recibe 1-5 imágenes en base64, detecta el rostro en cada una,
    genera el embedding y devuelve el promedio.

    Respuesta:
    - embedding: vector de floats (512 dimensiones con Facenet512)
    - imagenes_procesadas: cuántas imágenes se usaron exitosamente
    - modelo: nombre del modelo utilizado
    """
    if not solicitud.imagenes:
        raise HTTPException(status_code=422, detail="Debe enviar al menos una imagen.")
    if len(solicitud.imagenes) > 10:
        raise HTTPException(status_code=422, detail="Máximo 10 imágenes por solicitud.")

    embeddings_validos: List[List[float]] = []
    errores: List[str] = []

    for idx, imagen_b64 in enumerate(solicitud.imagenes):
        try:
            img_bgr = base64_a_imagen_bgr(imagen_b64)
            emb = obtener_embedding(img_bgr)
            embeddings_validos.append(emb)
            logger.info(f"Imagen {idx + 1}: embedding generado correctamente")
        except ValueError as e:
            msg = f"Imagen {idx + 1}: {str(e)}"
            errores.append(msg)
            logger.warning(msg)
        except Exception as e:
            msg = f"Imagen {idx + 1}: error al procesar — {str(e)}"
            errores.append(msg)
            logger.error(msg)

    if not embeddings_validos:
        raise HTTPException(
            status_code=422,
            detail={
                "mensaje": "No se pudo detectar ningún rostro en las imágenes enviadas.",
                "errores": errores,
            },
        )

    emb_final = embedding_promedio(embeddings_validos)

    return {
        "embedding": emb_final,
        "dimensiones": len(emb_final),
        "imagenes_procesadas": len(embeddings_validos),
        "imagenes_con_error": len(errores),
        "errores": errores,
        "modelo": MODELO_FACIAL,
    }


@app.post("/comparar-rostro", tags=["Reconocimiento"], dependencies=[Depends(verificar_api_key)])
def comparar_rostro(solicitud: SolicitudComparacion):
    """
    Recibe una imagen capturada y una lista de embeddings registrados.
    Compara usando similitud coseno y devuelve el mejor match.

    Respuesta:
    - reconocido: bool
    - id_empleado: int o null
    - confianza: float (0-100)
    - umbral_usado: float
    """
    registros_a_comparar = solicitud.embeddings_registrados if solicitud.embeddings_registrados else EMBEDDINGS_CACHE

    if not registros_a_comparar:
        raise HTTPException(
            status_code=422,
            detail="No hay embeddings registrados ni en la petición ni en caché para comparar.",
        )
    
    if not solicitud.imagen and not solicitud.embedding:
        raise HTTPException(
            status_code=422,
            detail="Debe enviar una imagen o un vector de embedding.",
        )

    try:
        if solicitud.embedding:
            emb_capturado = solicitud.embedding
        else:
            img_bgr = base64_a_imagen_bgr(solicitud.imagen)
            emb_capturado = obtener_embedding(img_bgr)
    except ValueError as e:
        raise HTTPException(
            status_code=400,
            detail=str(e),
        )
    except Exception as e:
        raise HTTPException(
            status_code=500,
            detail=f"Error al procesar la imagen capturada: {str(e)}",
        )

    mejor_similitud = -1.0
    mejor_id_empleado: Optional[int] = None

    for registro in registros_a_comparar:
        try:
            sim = similitud_coseno(emb_capturado, registro.embedding)
            logger.info(f"Empleado {registro.id_empleado}: similitud={sim:.4f}")
            if sim > mejor_similitud:
                mejor_similitud = sim
                mejor_id_empleado = registro.id_empleado
        except Exception as e:
            logger.warning(f"Error comparando empleado {registro.id_empleado}: {e}")

    reconocido = mejor_similitud >= UMBRAL_CONFIANZA
    confianza_pct = round(mejor_similitud * 100, 2)

    logger.info(
        f"Resultado: {'RECONOCIDO' if reconocido else 'NO RECONOCIDO'} | "
        f"Empleado={mejor_id_empleado} | Confianza={confianza_pct}%"
    )

    return {
        "reconocido": reconocido,
        "id_empleado": mejor_id_empleado if reconocido else None,
        "confianza": confianza_pct,
        "similitud_raw": round(mejor_similitud, 6),
        "umbral_usado": UMBRAL_CONFIANZA,
        "modelo": MODELO_FACIAL,
    }
