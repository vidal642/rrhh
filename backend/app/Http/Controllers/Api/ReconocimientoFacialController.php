<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\RespuestaJson;
use App\Http\Traits\RegistraLogs;
use App\Models\Asistencia;
use App\Models\EmpleadoRostro;
use App\Models\ReconocimientoFacial;
use App\Models\Empleado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class ReconocimientoFacialController extends Controller
{
    use RespuestaJson, RegistraLogs;

    // ─────────────────────────────────────────────────────────────────────────
    // Constantes de resultado para auditoría
    // ─────────────────────────────────────────────────────────────────────────

    const RESULTADO_EXITO  = 'exito';
    const RESULTADO_FALLO  = 'fallo';

    // ─────────────────────────────────────────────────────────────────────────
    // VALIDAR EMPLEADO (PANEL ADMIN)
    // ─────────────────────────────────────────────────────────────────────────

    public function validarEmpleado(Request $request): JsonResponse
    {
        $request->validate([
            'employee_code' => 'required|string|max:50',
        ], [
            'employee_code.required' => 'El código de empleado es obligatorio.',
        ]);

        // Buscar al empleado por su id_empleado
        $empleado = Empleado::where('id_empleado', $request->employee_code)->first();

        if (!$empleado) {
            return $this->respuestaError('No se encontró ningún empleado con el ID proporcionado.', 404);
        }

        return $this->respuestaExito([
            'id_empleado' => $empleado->id_empleado,
            'nombre_completo' => $empleado->nombre . ' ' . $empleado->apellidos,
            'rostro_registrado' => EmpleadoRostro::where('id_empleado', $empleado->id_empleado)->where('estado', 'activo')->exists()
        ], 'Empleado validado correctamente.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OBTENER EMPLEADOS DISPONIBLES PARA ROSTRO (PANEL ADMIN)
    // ─────────────────────────────────────────────────────────────────────────

    public function empleadosDisponibles(Request $request): JsonResponse
    {
        try {
            $consulta = Empleado::with('cargo')->where('estado', 'Activo');

            if ($request->filled('buscar')) {
                $termino = $request->buscar;
                $consulta->where(function ($q) use ($termino) {
                    $q->where('nombre', 'like', "%{$termino}%")
                      ->orWhere('apellido', 'like', "%{$termino}%")
                      ->orWhere('codigo_empleado', 'like', "%{$termino}%");
                });
            }

            $empleados = $consulta->orderBy('nombre')->get()->map(function ($empleado) {
                return [
                    'id_empleado' => $empleado->id_empleado,
                    'nombre_completo' => $empleado->nombre . ' ' . $empleado->apellido,
                    'codigo_empleado' => $empleado->codigo_empleado,
                    'cargo' => $empleado->cargo ? $empleado->cargo->nombre : 'Sin Cargo',
                    'rostro_registrado' => EmpleadoRostro::where('id_empleado', $empleado->id_empleado)
                                                         ->where('estado', 'activo')
                                                         ->exists()
                ];
            });

            return $this->respuestaExito($empleados, 'Empleados disponibles obtenidos correctamente.');
        } catch (\Exception $e) {
            Log::error('empleadosDisponibles: ' . $e->getMessage());
            return $this->respuestaServidor('Error al obtener los empleados.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VERIFICAR REGISTRO DE ROSTRO
    // ─────────────────────────────────────────────────────────────────────────

    public function verificarRegistroRostro(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            $rol = strtolower($usuario->rol ?? '');

            if (! $usuario->id_empleado || $rol === 'admin' || $rol === 'administrador') {
                return $this->respuestaExito([
                    'rostro_registrado' => true,
                    'es_admin'          => true,
                    'id_empleado'       => $usuario->id_empleado,
                ], 'Usuario administrador — sin requisito facial');
            }

            $tieneRostro = EmpleadoRostro::where('id_empleado', $usuario->id_empleado)
                ->where('estado', 'activo')
                ->exists();

            return $this->respuestaExito([
                'rostro_registrado' => $tieneRostro,
                'es_admin'          => false,
                'id_empleado'       => $usuario->id_empleado,
            ], $tieneRostro ? 'Rostro registrado' : 'Sin rostro registrado');

        } catch (\Exception $e) {
            Log::error('verificarRegistroRostro: ' . $e->getMessage());
            return $this->respuestaServidor('Error al verificar el estado del rostro');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRAR ROSTRO
    // ─────────────────────────────────────────────────────────────────────────

    public function registrarRostro(Request $request): JsonResponse
    {
        $request->validate([
            'embedding'         => 'required|array|min:128|max:512',
            'embedding.*'       => 'required|numeric',
            'imagen_referencia' => 'required|string',
        ], [
            'embedding.required'         => 'El descriptor facial es requerido.',
            'embedding.min'              => 'El descriptor debe tener al menos 128 dimensiones.',
            'imagen_referencia.required' => 'La imagen de referencia es requerida.',
        ]);

        try {
            $usuario = $request->user();

            if (! $usuario->id_empleado) {
                return $this->respuestaError('Solo los empleados pueden registrar su rostro.', 403);
            }

            if ($errorDuplicado = $this->validarRostroDuplicado($request->embedding, $usuario->id_empleado)) {
                return $errorDuplicado;
            }

            // Usa la función unificada guardarImagen()
            $rutaImagen = $this->guardarImagen(
                $request->imagen_referencia,
                'rostros',
                $usuario->id_empleado
            );

            EmpleadoRostro::where('id_empleado', $usuario->id_empleado)
                ->update(['estado' => 'inactivo']);

            $rostro = EmpleadoRostro::create([
                'id_empleado'       => $usuario->id_empleado,
                'embedding'         => $request->embedding,
                'imagen_referencia' => $rutaImagen,
                'modelo_usado'      => 'face-api.js (SSD Mobilenet v1)',
                'estado'            => 'activo',
                'observaciones'     => 'Registrado desde cliente web',
            ]);

            Log::info("Rostro registrado para empleado {$usuario->id_empleado} (id_rostro={$rostro->id_rostro})");
            $this->registrarLogReconocimiento($usuario->id_empleado, 'REGISTRO_ROSTRO', "Rostro registrado (id_rostro={$rostro->id_rostro})");

            // Sincronizar con FastAPI
            $this->sincronizarEmbeddingsFastAPI();

            return $this->respuestaExito([
                'id_rostro'         => $rostro->id_rostro,
                'imagen_referencia' => $rutaImagen,
            ], 'Rostro registrado correctamente', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            Log::error("registrarRostro error: " . $e->getMessage());
            return $this->respuestaServidor('Error al registrar el rostro');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ACTUALIZAR ROSTRO
    // ─────────────────────────────────────────────────────────────────────────

    public function actualizarRostro(Request $request, $id): JsonResponse
    {
        $request->validate([
            'embedding'         => 'required|array|min:128|max:512',
            'embedding.*'       => 'required|numeric',
            'imagen_referencia' => 'required|string',
        ], [
            'embedding.required'         => 'El descriptor facial es requerido.',
            'embedding.min'              => 'El descriptor debe tener al menos 128 dimensiones.',
            'imagen_referencia.required' => 'La imagen de referencia es requerida.',
        ]);

        try {
            $usuario = $request->user();
            $rol = strtolower($usuario->rol ?? '');

            // Validar que sea admin o el mismo empleado
            if ($rol !== 'admin' && $rol !== 'administrador' && $usuario->id_empleado != $id) {
                return $this->respuestaError('No tiene permisos para actualizar este rostro.', 403);
            }

            if ($errorDuplicado = $this->validarRostroDuplicado($request->embedding, $id)) {
                return $errorDuplicado;
            }

            // Usa la función unificada guardarImagen()
            $rutaImagen = $this->guardarImagen(
                $request->imagen_referencia,
                'rostros',
                $id
            );

            // Inactivar el anterior
            EmpleadoRostro::where('id_empleado', $id)
                ->update(['estado' => 'inactivo']);

            // Crear el nuevo (sobrescribir conceptualmente)
            $rostro = EmpleadoRostro::create([
                'id_empleado'       => $id,
                'embedding'         => $request->embedding,
                'imagen_referencia' => $rutaImagen,
                'modelo_usado'      => 'face-api.js (SSD Mobilenet v1)',
                'estado'            => 'activo',
                'observaciones'     => 'Actualizado desde cliente web',
            ]);

            Log::info("Rostro actualizado para empleado {$id} (id_rostro={$rostro->id_rostro})");
            $this->registrarLogReconocimiento($id, 'REGISTRO_ROSTRO', "Rostro actualizado (id_rostro={$rostro->id_rostro})");

            // Sincronizar con FastAPI
            $this->sincronizarEmbeddingsFastAPI();

            return $this->respuestaExito([
                'id_rostro'         => $rostro->id_rostro,
                'imagen_referencia' => $rutaImagen,
            ], 'Rostro actualizado correctamente', 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            Log::error("actualizarRostro error: " . $e->getMessage());
            return $this->respuestaServidor('Error al actualizar el rostro');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OBTENER EMBEDDINGS
    // ─────────────────────────────────────────────────────────────────────────

    public function obtenerEmbeddings(): JsonResponse
    {
        try {
            $rostros = EmpleadoRostro::activos()
                ->with('empleado:id_empleado,nombre')
                ->select('id_empleado', 'embedding')
                ->get();

            $embeddings = $rostros->map(fn($rostro) => [
                'id_empleado' => $rostro->id_empleado,
                'nombre'      => $rostro->empleado->nombre ?? 'Desconocido',
                'embedding'   => is_string($rostro->embedding)
                    ? json_decode($rostro->embedding, true)
                    : $rostro->embedding,
            ]);

            return $this->respuestaExito($embeddings, 'Embeddings obtenidos correctamente');
        } catch (\Exception $e) {
            Log::error("obtenerEmbeddings error: " . $e->getMessage());
            return $this->respuestaServidor('Error al obtener embeddings');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRAR ASISTENCIA FACIAL (endpoint público del controlador)
    // ─────────────────────────────────────────────────────────────────────────

    public function registrarAsistenciaFacial(Request $request): JsonResponse
    {
        $request->validate([
            'id_empleado' => 'required|integer|exists:empleado,id_empleado',
            'confianza'   => 'required|numeric|min:0|max:100',
            'imagen'      => 'nullable|string',
        ]);

        try {
            if ($request->confianza < 55.00) {
                return $this->respuestaError('Rostro no coincide. Similitud menor al 55%.', 403);
            }

            $asistenciaExistente = Asistencia::where('id_empleado', $request->id_empleado)
                ->whereDate('fecha', now()->toDateString())
                ->first();

            if ($asistenciaExistente && $asistenciaExistente->hora_salida) {
                // Auditar el intento fallido (ya registrado entrada y salida)
                $this->auditarReconocimiento(
                    idEmpleado: $request->id_empleado,
                    confianza:  $request->confianza,
                    resultado:  self::RESULTADO_FALLO,
                    imagen:     $request->imagen,
                );
                return $this->respuestaError('Ya has registrado tu entrada y salida por el día de hoy.', 400);
            }

            // Guardar imagen de verificación si fue enviada
            $rutaImagen = $request->imagen
                ? $this->guardarImagen($request->imagen, 'verificaciones', $request->id_empleado)
                : null;

            $asistencia = $this->registrarAsistenciaFacialInterno(
                $request->id_empleado,
                $request->confianza,
                $rutaImagen
            );

            // ── Auditoría: guardar en reconocimiento_facial ───────────────
            $this->auditarReconocimiento(
                idEmpleado:   $request->id_empleado,
                confianza:    $request->confianza,
                resultado:    self::RESULTADO_EXITO,
                imagen:       $rutaImagen,
                idAsistencia: $asistencia->id_asistencia
            );

            return $this->respuestaExito([
                'id_asistencia' => $asistencia->id_asistencia,
                'hora_entrada'  => $asistencia->hora_entrada,
                'fecha'         => $asistencia->fecha,
            ], 'Asistencia registrada correctamente', 201);

        } catch (\Exception $e) {
            Log::error("registrarAsistenciaFacial error: " . $e->getMessage());
            return $this->respuestaServidor('Error al registrar la asistencia');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTRAR ASISTENCIA GEO-FACIAL (GPS + Rostro)
    // ─────────────────────────────────────────────────────────────────────────

    public function registrarAsistenciaGeoFacial(Request $request): JsonResponse
    {
        $request->validate([
            'id_empleado' => 'required|integer|exists:empleado,id_empleado',
            'confianza'   => 'required|numeric|min:0|max:100',
            'imagen'      => 'nullable|string',
            'latitud'     => 'required|numeric',
            'longitud'    => 'required|numeric',
        ]);

        try {
            // 1. Validar Distancia GPS
            $empresaLat = env('EMPRESA_LAT', -17.394291);
            $empresaLon = env('EMPRESA_LON', -66.074135);

            $distancia = $this->calcularDistancia($empresaLat, $empresaLon, $request->latitud, $request->longitud);

            if ($distancia > 500) {
                $this->auditarReconocimiento(
                    $request->id_empleado,
                    $request->confianza,
                    self::RESULTADO_FALLO,
                    $request->imagen
                );
                return response()->json([
                    'success' => false,
                    'message' => 'Estás fuera del rango permitido para registrar asistencia.',
                    'distancia_metros' => round($distancia, 2)
                ], 403);
            }

            // 2. Validar Confianza Facial
            if ($request->confianza < 55.00) {
                return $this->respuestaError('Rostro no coincide. Similitud menor al 55%.', 403);
            }

            // 3. Validar si ya registró salida
            $asistenciaExistente = Asistencia::where('id_empleado', $request->id_empleado)
                ->whereDate('fecha', now()->toDateString())
                ->first();

            if ($asistenciaExistente && $asistenciaExistente->hora_salida) {
                $this->auditarReconocimiento($request->id_empleado, $request->confianza, self::RESULTADO_FALLO, $request->imagen);
                return $this->respuestaError('Ya has registrado tu entrada y salida por el día de hoy.', 400);
            }

            // 4. Guardar imagen
            $rutaImagen = $request->imagen
                ? $this->guardarImagen($request->imagen, 'verificaciones', $request->id_empleado)
                : null;

            // 5. Registrar/Actualizar Asistencia
            $asistencia = $this->registrarAsistenciaFacialInterno(
                $request->id_empleado,
                $request->confianza,
                $rutaImagen
            );

            // Actualizar la latitud, longitud y distancia si es el registro de entrada
            if ($asistencia->wasRecentlyCreated) {
                $asistencia->update([
                    'latitud' => $request->latitud,
                    'longitud' => $request->longitud,
                    'distancia' => $distancia,
                ]);
            }

            // 6. Auditoría
            $this->auditarReconocimiento(
                idEmpleado:   $request->id_empleado,
                confianza:    $request->confianza,
                resultado:    self::RESULTADO_EXITO,
                imagen:       $rutaImagen,
                idAsistencia: $asistencia->id_asistencia
            );

            return $this->respuestaExito([
                'id_asistencia'    => $asistencia->id_asistencia,
                'hora_entrada'     => $asistencia->hora_entrada,
                'hora_salida'      => $asistencia->hora_salida,
                'fecha'            => $asistencia->fecha,
                'distancia_metros' => round($distancia, 2)
            ], 'Asistencia registrada correctamente', 201);

        } catch (\Exception $e) {
            Log::error("registrarAsistenciaGeoFacial error: " . $e->getMessage());
            return $this->respuestaServidor('Error al registrar la asistencia geofacial');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Valida si un descriptor facial ya pertenece a otro empleado distinto.
     */
    private function validarRostroDuplicado(array $nuevoEmbedding, int $idEmpleadoExcluido): ?JsonResponse
    {
        $embeddingsRegistrados = EmpleadoRostro::activos()->get()->map(function($r) {
            return [
                'id_empleado' => $r->id_empleado,
                'embedding' => is_string($r->embedding) ? json_decode($r->embedding, true) : $r->embedding
            ];
        })->toArray();

        if (empty($embeddingsRegistrados)) {
            return null;
        }

        $usarRespaldoPhp = true;
        try {
            $respuesta = Http::timeout(3)->withHeaders([
                'X-API-Key' => env('FACE_SERVICE_API_KEY', 'mi_llave_secreta_rrhh_2026')
            ])->post(env('FACE_SERVICE_URL', 'http://localhost:8000') . '/comparar-rostro', [
                'embedding' => $nuevoEmbedding
            ]);

            if ($respuesta->status() === 422 && str_contains($respuesta->json('detail'), 'caché')) {
                $this->sincronizarEmbeddingsFastAPI($embeddingsRegistrados);
                $respuesta = Http::timeout(3)->withHeaders([
                    'X-API-Key' => env('FACE_SERVICE_API_KEY', 'mi_llave_secreta_rrhh_2026')
                ])->post(env('FACE_SERVICE_URL', 'http://localhost:8000') . '/comparar-rostro', [
                    'embedding' => $nuevoEmbedding
                ]);
            }

            if ($respuesta->successful()) {
                $usarRespaldoPhp = false;
                if ($respuesta->json('reconocido')) {
                    $idReconocido = $respuesta->json('id_empleado');
                    if ($idReconocido != $idEmpleadoExcluido) {
                        return $this->respuestaError('Este rostro ya se encuentra registrado para otro empleado.', 409);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('FastAPI no disponible para comparar rostros, usando respaldo en PHP: ' . $e->getMessage());
        }

        if ($usarRespaldoPhp) {
            foreach ($embeddingsRegistrados as $registro) {
                $descriptorGuardado = $registro['embedding'];
                if (is_array($descriptorGuardado) && count($descriptorGuardado) === 128) {
                    $distancia = $this->calcularDistanciaEuclidianaOptimizado($nuevoEmbedding, $descriptorGuardado);
                    $similitud = (1 - $distancia) * 100;
                    
                    if ($similitud >= 75.00 && $registro['id_empleado'] != $idEmpleadoExcluido) {
                        return $this->respuestaError('Este rostro ya se encuentra registrado para otro empleado.', 409);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Lógica interna de registro/actualización de asistencia facial.
     * Separada del endpoint para permitir llamadas sin contexto HTTP.
     */
    private function registrarAsistenciaFacialInterno(
        int $idEmpleado,
        float $confianza,
        ?string $rutaImagen = null
    ): Asistencia {
        $hoy = now()->toDateString();

        $asistenciaExistente = Asistencia::where('id_empleado', $idEmpleado)
            ->whereDate('fecha', $hoy)
            ->first();

        if ($asistenciaExistente) {
            if (! $asistenciaExistente->hora_salida) {
                $horaEntrada = \Carbon\Carbon::parse(
                    $asistenciaExistente->fecha . ' ' . $asistenciaExistente->hora_entrada
                );
                // Ignorar si han pasado menos de 1 minuto (doble-tap accidental)
                // Comentado temporalmente para pruebas rápidas
                /*if (now()->diffInMinutes($horaEntrada) < 1) {
                    return $asistenciaExistente;
                }*/

                $asistenciaExistente->update([
                    'hora_salida'         => now()->toTimeString(),
                    'confianza_facial'    => $confianza,
                    'imagen_verificacion' => $rutaImagen,
                ]);
            }
            return $asistenciaExistente;
        }

        $horaActual = now();
        $horaEntradaLimite = \Carbon\Carbon::today()->setTime(8, 0, 0);

        if ($horaActual->greaterThan($horaEntradaLimite)) {
            $estadoAsistencia = 'TARDE';
            $estadoGeneral = 'Retraso';
        } else {
            $estadoAsistencia = 'PUNTUAL';
            $estadoGeneral = 'Presente';
        }

        return Asistencia::create([
            'id_empleado'         => $idEmpleado,
            'fecha'               => $hoy,
            'hora_entrada'        => now()->toTimeString(),
            'hora_salida'         => null,
            'horas_trabajadas'    => null,
            'estado'              => $estadoGeneral,
            'estado_asistencia'   => $estadoAsistencia,
            'metodo_registro'     => 'Reconocimiento Facial',
            'confianza_facial'    => $confianza,
            'porcentaje_similitud'=> $confianza,
            'imagen_verificacion' => $rutaImagen,
        ]);
    }

    /**
     * Guarda un intento de reconocimiento facial en la tabla reconocimiento_facial.
     *
     * Antes: ReconocimientoFacial existía en BD pero NUNCA se usaba.
     * Ahora: Cada intento (exitoso o fallido) queda registrado para auditoría.
     *
     * @param int      $idEmpleado
     * @param float    $confianza      Nivel de confianza del reconocimiento (0-100)
     * @param string   $resultado      'exito' | 'fallo'
     * @param mixed    $imagen         Base64 string o ruta ya guardada
     * @param int|null $idAsistencia   ID de la asistencia generada (null si falló)
     */
    private function auditarReconocimiento(
        int $idEmpleado,
        float $confianza,
        string $resultado,
        mixed $imagen = null,
        ?int $idAsistencia = null
    ): void {
        try {
            ReconocimientoFacial::create([
                'id_empleado'     => $idEmpleado,
                'id_asistencia'   => $idAsistencia,
                'resultado'       => $resultado,
                'confianza'       => $confianza,
                'imagen_capturada'=> $imagen,
                'fecha_hora'      => now(),
            ]);

            $this->registrarLogReconocimiento(
                $idEmpleado,
                $resultado === self::RESULTADO_EXITO ? 'RECONOCIMIENTO_EXITOSO' : 'RECONOCIMIENTO_FALLIDO',
                "Validación de asistencia - Confianza: " . round($confianza, 2) . "%"
            );
        } catch (\Exception $e) {
            // La auditoría no debe interrumpir el flujo principal
            Log::warning("No se pudo guardar auditoría facial: " . $e->getMessage());
        }
    }

    /**
     * Guarda una imagen en base64 en el disco de almacenamiento.
     *
     * Función unificada que reemplaza a las dos anteriores:
     *   - guardarImagenReferencia() → usar con $carpeta = 'rostros'
     *   - guardarImagenVerificacion() → usar con $carpeta = 'verificaciones'
     *
     * @param  string $imagenBase64  Imagen en base64 (con o sin header data:image/...)
     * @param  string $carpeta       Subdirectorio dentro de storage/public/
     * @param  int    $empleadoId    ID del empleado (para nombre de archivo único)
     * @return string|null           Ruta relativa guardada, o null si falló
     */
    private function guardarImagen(string $imagenBase64, string $carpeta, int $empleadoId): ?string
    {
        try {
            // Eliminar header base64 si existe (data:image/jpeg;base64,...)
            if (str_contains($imagenBase64, ',')) {
                $imagenBase64 = explode(',', $imagenBase64, 2)[1];
            }
            $imagenBytes   = base64_decode($imagenBase64);
            
            // --- INICIO SEGURIDAD: Validar Magic Bytes ---
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($imagenBytes);
            
            $mimesPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mime, $mimesPermitidos)) {
                Log::warning("Intento de subida de archivo malicioso. Tipo detectado: {$mime}");
                throw new \Exception("El archivo enviado no es una imagen válida.");
            }
            // --- FIN SEGURIDAD ---

            $nombreArchivo = "{$carpeta}/empleado_{$empleadoId}_" . time() . '.jpg';
            Storage::disk('public')->put($nombreArchivo, $imagenBytes);

            return $nombreArchivo;
        } catch (\Exception $e) {
            Log::warning("No se pudo guardar imagen en {$carpeta}: " . $e->getMessage());
            return null;
        }
    }

    private function calcularDistancia($lat1, $lon1, $lat2, $lon2)
    {
        $radioTierra = 6371000;
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $radioTierra * $c;
    }

    private function calcularDistanciaEuclidiana(array $descriptor1, array $descriptor2): float
    {
        return $this->calcularDistanciaEuclidianaOptimizado($descriptor1, $descriptor2);
    }

    /**
     * Versión optimizada del cálculo de distancia euclidiana en PHP puro.
     * Evita el uso de pow() que es lento y usa un foreach rápido.
     */
    private function calcularDistanciaEuclidianaOptimizado(array $descriptor1, array $descriptor2): float
    {
        $suma = 0.0;
        foreach ($descriptor1 as $i => $v1) {
            $diff = (float)$v1 - (float)($descriptor2[$i] ?? 0);
            $suma += $diff * $diff;
            // Optimización: si la distancia ya es muy grande (ej > 0.5 que implica < 50% de similitud), salir temprano
            if ($suma > 0.5) return 1.0; 
        }
        return sqrt($suma);
    }

    /**
     * Sincroniza la caché de embeddings en memoria del microservicio FastAPI
     */
    private function sincronizarEmbeddingsFastAPI(?array $embeddings = null): void
    {
        try {
            if ($embeddings === null) {
                $embeddings = EmpleadoRostro::activos()->get()->map(function($r) {
                    return [
                        'id_empleado' => $r->id_empleado,
                        'embedding' => is_string($r->embedding) ? json_decode($r->embedding, true) : $r->embedding
                    ];
                })->toArray();
            }

            if (!empty($embeddings)) {
                Http::timeout(3)->withHeaders([
                    'X-API-Key' => env('FACE_SERVICE_API_KEY', 'mi_llave_secreta_rrhh_2026')
                ])->post(env('FACE_SERVICE_URL', 'http://localhost:8000') . '/sincronizar-embeddings', $embeddings);
                Log::info("Embeddings sincronizados exitosamente con FastAPI (" . count($embeddings) . " registros).");
            }
        } catch (\Exception $e) {
            Log::warning("No se pudo sincronizar embeddings con FastAPI: " . $e->getMessage());
        }
    }
}
