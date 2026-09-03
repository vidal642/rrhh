# ─── Imagen base ──────────────────────────────────────────────────────────────
FROM python:3.11-slim

# ─── Variables de entorno ─────────────────────────────────────────────────────
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    DEEPFACE_HOME=/app/.deepface \
    MODELO_FACIAL=Facenet512 \
    DETECTOR_BACKEND=opencv \
    UMBRAL_CONFIANZA=0.55

# ─── Dependencias del sistema (OpenCV necesita libGL) ─────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
    libgl1 \
    libglib2.0-0 \
    libsm6 \
    libxext6 \
    libxrender-dev \
    libgomp1 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ─── Directorio de trabajo ────────────────────────────────────────────────────
WORKDIR /app

# ─── Instalar dependencias Python ─────────────────────────────────────────────
COPY requirements.txt .
RUN pip install --no-cache-dir --upgrade pip \
    && pip install --no-cache-dir -r requirements.txt

# ─── Copiar código fuente ─────────────────────────────────────────────────────
COPY main.py .

# ─── Directorio para modelos cacheados (montado como volumen) ─────────────────
RUN mkdir -p /app/.deepface

# ─── Puerto ───────────────────────────────────────────────────────────────────
EXPOSE 8001

# ─── Comando de inicio ────────────────────────────────────────────────────────
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001", "--workers", "1"]
