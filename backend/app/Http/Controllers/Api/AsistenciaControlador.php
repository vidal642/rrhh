<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\RespuestaJson;
use App\Http\Traits\RegistraLogs;
use App\Models\Asistencia;
use App\Models\Configuracion;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DynamicReportExport;

class AsistenciaControlador extends Controller
{
    use RespuestaJson, RegistraLogs;

    const ESTADOS = ['Presente', 'Falta', 'Retraso', 'Permiso', 'Vacación'];

    const METODOS = ['Manual', 'Administrador', 'QR', 'Facial'];

    public function index(Request $request): JsonResponse
    {
        try {
            $fechaFiltro = null;
            $esHistorico = false;
            
            if ($request->filled('fecha')) {
                $fechaFiltro = \Carbon\Carbon::parse($request->fecha)->startOfDay();
                if ($fechaFiltro->lessThan(\Carbon\Carbon::today()->startOfDay())) {
                    $esHistorico = true;
                }
            }

            if ($esHistorico) {
                $consulta = \App\Models\Empleado::with(['departamento', 'cargo'])
                    ->where('empleado.estado', 'Activo')
                    ->leftJoin('asistencia', function ($join) use ($request) {
                        $join->on('empleado.id_empleado', '=', 'asistencia.id_empleado')
                             ->whereDate('asistencia.fecha', '=', $request->fecha);
                    })
                    ->select(
                        'empleado.*', 
                        'asistencia.id_asistencia',
                        \Illuminate\Support\Facades\DB::raw("COALESCE(asistencia.fecha, '{$request->fecha}') as fecha"),
                        'asistencia.hora_entrada',
                        'asistencia.hora_salida',
                        'asistencia.horas_trabajadas',
                        \Illuminate\Support\Facades\DB::raw("COALESCE(asistencia.estado, 'Falta') as estado_asistencia_final"),
                        'asistencia.estado as estado_original',
                        'asistencia.estado_asistencia',
                        'asistencia.metodo_registro'
                    );

                if ($request->filled('estado')) {
                    if ($request->estado === 'Falta') {
                        $consulta->where(function($q) {
                            $q->where('asistencia.estado', 'Falta')
                              ->orWhereNull('asistencia.id_asistencia');
                        });
                    } else {
                        $consulta->where('asistencia.estado', $request->estado);
                    }
                }
                
                if ($request->filled('busqueda')) {
                    $termino = '%' . $request->busqueda . '%';
                    $consulta->where(function($q) use ($termino) {
                        $q->where('empleado.nombre', 'like', $termino)
                          ->orWhere('empleado.apellido', 'like', $termino);
                    });
                }

                if ($request->filled('id_empleado') || $request->filled('empleado_id')) {
                    $consulta->where('empleado.id_empleado', $request->input('id_empleado') ?? $request->input('empleado_id'));
                }

                if ($request->filled('id_departamento') || $request->filled('departamento_id')) {
                    $consulta->where('empleado.id_departamento', $request->input('id_departamento') ?? $request->input('departamento_id'));
                }
                
            } else {
                $consulta = Asistencia::with(['empleado.departamento', 'empleado.cargo']);

                if ($request->filled('fecha')) {
                    $consulta->whereDate('fecha', $request->fecha);
                }

                if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
                    $consulta->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
                }

                if ($request->filled('id_empleado') || $request->filled('empleado_id')) {
                    $consulta->where('id_empleado', $request->input('id_empleado') ?? $request->input('empleado_id'));
                }

                if ($request->filled('estado')) {
                    $consulta->where('estado', $request->estado);
                }
                
                if ($request->filled('busqueda')) {
                    $termino = '%' . $request->busqueda . '%';
                    $consulta->whereHas('empleado', function($q) use ($termino) {
                        $q->where('nombre', 'like', $termino)
                          ->orWhere('apellido', 'like', $termino);
                    });
                }

                if ($request->filled('id_departamento') || $request->filled('departamento_id')) {
                    $consulta->whereHas('empleado', function ($q) use ($request) {
                        $q->where('id_departamento', $request->input('id_departamento') ?? $request->input('departamento_id'));
                    });
                }

                if (!$request->filled('fecha') && !$request->filled('fecha_inicio') && !$request->filled('id_empleado') && !$request->filled('empleado_id')) {
                    $consulta->whereDate('fecha', today());
                }
            }

            $perPage = $request->input('per_page', 10);
            
            if ($esHistorico) {
                if ($perPage === 'all' || $perPage == -1) {
                    $asistenciasPag = $consulta->orderBy('empleado.nombre', 'asc')->get();
                } else {
                    $asistenciasPag = $consulta->orderBy('empleado.nombre', 'asc')->paginate($perPage);
                }
                
                $items = ($perPage === 'all' || $perPage == -1) ? $asistenciasPag : $asistenciasPag->items();
                
                $itemsMapped = collect($items)->map(function ($item) {
                    return [
                        'id_asistencia' => $item->id_asistencia,
                        'id_empleado' => $item->id_empleado,
                        'fecha' => $item->fecha,
                        'hora_entrada' => $item->hora_entrada,
                        'hora_salida' => $item->hora_salida,
                        'horas_trabajadas' => $item->horas_trabajadas,
                        'estado' => $item->estado_asistencia_final,
                        'estado_asistencia' => $item->estado_asistencia,
                        'metodo_registro' => $item->metodo_registro,
                        'empleado' => [
                            'id_empleado' => $item->id_empleado,
                            'nombre' => $item->nombre,
                            'apellido' => $item->apellido,
                            'ci' => $item->ci,
                            'departamento' => $item->departamento,
                            'cargo' => $item->cargo
                        ]
                    ];
                });
                
                if ($perPage === 'all' || $perPage == -1) {
                    $datos = [
                        'data' => $itemsMapped,
                        'current_page' => 1,
                        'last_page' => 1,
                        'total' => $itemsMapped->count()
                    ];
                } else {
                    $datos = [
                        'data' => $itemsMapped,
                        'current_page' => $asistenciasPag->currentPage(),
                        'last_page' => $asistenciasPag->lastPage(),
                        'total' => $asistenciasPag->total()
                    ];
                }
            } else {
                if ($perPage === 'all' || $perPage == -1) {
                    $asistencias = $consulta->orderBy('fecha', 'desc')->orderBy('created_at', 'desc')->get();
                    $datos = [
                        'data' => $asistencias,
                        'current_page' => 1,
                        'last_page' => 1,
                        'total' => $asistencias->count()
                    ];
                } else {
                    $asistenciasPag = $consulta->orderBy('fecha', 'desc')->orderBy('created_at', 'desc')->paginate($perPage);
                    $datos = [
                        'data' => $asistenciasPag->items(),
                        'current_page' => $asistenciasPag->currentPage(),
                        'last_page' => $asistenciasPag->lastPage(),
                        'total' => $asistenciasPag->total()
                    ];
                }
            }

            return $this->respuestaExito($datos, 'Asistencias obtenidas correctamente');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error($e->getMessage());
            return $this->respuestaServidor();
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validado = $request->validate([
                'id_empleado'     => 'required|exists:empleado,id_empleado',
                'fecha'           => 'required|date|date_equals:today',
                'hora_entrada'    => 'nullable|date_format:H:i:s',
                'hora_salida'     => 'nullable|date_format:H:i:s|after:hora_entrada',
                'estado'          => ['required', Rule::in(self::ESTADOS)],
                'metodo_registro' => ['nullable', Rule::in(self::METODOS)],
            ], [
                'id_empleado.required' => 'Seleccione un empleado válido.',
                'id_empleado.exists'   => 'Seleccione un empleado válido.',
                'fecha.required'       => 'La fecha es obligatoria.',
                'fecha.date'               => 'La fecha ingresada no es correcta.',
                'fecha.date_equals'        => 'Solo se puede registrar la asistencia de la fecha actual.',
                'hora_entrada.date_format' => 'El formato de la hora de entrada no es correcto.',
                'hora_salida.date_format'  => 'El formato de la hora de salida no es correcto.',
                'hora_salida.after'    => 'La hora de salida no puede ser menor o igual que la hora de entrada.',
                'estado.required'      => 'Seleccione un estado válido.',
                'estado.in'            => 'Seleccione un estado válido.',
                'metodo_registro.in'   => 'Seleccione un método de registro válido.',
            ]);

            $existe = Asistencia::where('id_empleado', $validado['id_empleado'])
                ->whereDate('fecha', $validado['fecha'])
                ->exists();

            if ($existe) {
                return $this->respuestaError(
                    "Ya existe un registro de asistencia para este empleado en la fecha indicada.",
                    409
                );
            }

            $horasTrabajadas = null;
            if (!empty($validado['hora_entrada']) && !empty($validado['hora_salida'])) {
                $entrada = \Carbon\Carbon::createFromFormat('H:i:s', $validado['hora_entrada']);
                $salida = \Carbon\Carbon::createFromFormat('H:i:s', $validado['hora_salida']);
                $horasTrabajadas = round($entrada->diffInMinutes($salida) / 60, 2);
            }

            if (isset($validado['hora_entrada'])) {
                // Obtener hora de entrada y tolerancia desde configuración
                $horaEntradaConfig = \App\Models\Configuracion::getString('hora_entrada', '08:00');
                $toleranciaMin     = \App\Models\Configuracion::getInt('tolerancia_minutos', 0);

                $horaEntrada = \Carbon\Carbon::createFromFormat('H:i:s', $validado['hora_entrada']);
                $horaLimite  = \Carbon\Carbon::createFromFormat('H:i', $horaEntradaConfig)
                    ->addMinutes($toleranciaMin);

                if ($horaEntrada->greaterThan($horaLimite)) {
                    $validado['estado_asistencia'] = 'TARDE';
                    if (isset($validado['estado']) && $validado['estado'] === 'Presente') {
                        $validado['estado'] = 'Retraso';
                    }
                } else {
                    $validado['estado_asistencia'] = 'PUNTUAL';
                }
            }

            $validado['horas_trabajadas'] = $horasTrabajadas;
            $validado['metodo_registro'] = $validado['metodo_registro'] ?? 'Administrador';

            $asistencia = Asistencia::create($validado);
            $asistencia->load(['empleado.departamento', 'empleado.cargo']);

            return $this->respuestaCreado($asistencia, 'Asistencia registrada correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $asistencia = Asistencia::with(['empleado.departamento', 'empleado.cargo'])->findOrFail($id);
            return $this->respuestaExito($asistencia, 'Asistencia obtenida correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $asistencia = Asistencia::findOrFail($id);

            $validado = $request->validate([
                'id_empleado'     => 'sometimes|exists:empleado,id_empleado',
                'fecha'           => 'sometimes|date|date_equals:today',
                'hora_entrada'    => 'nullable|date_format:H:i:s',
                'hora_salida'     => 'nullable|date_format:H:i:s|after:hora_entrada',
                'estado'          => ['sometimes', Rule::in(self::ESTADOS)],
                'metodo_registro' => ['nullable', Rule::in(self::METODOS)],
            ], [
                'id_empleado.exists'   => 'Seleccione un empleado válido.',
                'fecha.date'               => 'La fecha ingresada no es correcta.',
                'fecha.date_equals'        => 'Solo se puede registrar la asistencia de la fecha actual.',
                'hora_entrada.date_format' => 'El formato de la hora de entrada no es correcto.',
                'hora_salida.date_format'  => 'El formato de la hora de salida no es correcto.',
                'hora_salida.after'    => 'La hora de salida no puede ser menor o igual que la hora de entrada.',
                'estado.in'            => 'Seleccione un estado válido.',
                'metodo_registro.in'   => 'Seleccione un método de registro válido.',
            ]);

            $horaEntrada = $validado['hora_entrada'] ?? $asistencia->hora_entrada;
            $horaSalida = $validado['hora_salida'] ?? $asistencia->hora_salida;
            
            if (!empty($horaEntrada)) {
                // Obtener hora de entrada y tolerancia desde configuración
                $horaEntradaConfig = \App\Models\Configuracion::getString('hora_entrada', '08:00');
                $toleranciaMin     = \App\Models\Configuracion::getInt('tolerancia_minutos', 0);

                $horaEntradaObj = \Carbon\Carbon::createFromFormat('H:i:s', $horaEntrada);
                $horaLimite     = \Carbon\Carbon::createFromFormat('H:i', $horaEntradaConfig)
                    ->addMinutes($toleranciaMin);

                if ($horaEntradaObj->greaterThan($horaLimite)) {
                    $validado['estado_asistencia'] = 'TARDE';
                    if (($validado['estado'] ?? $asistencia->estado) === 'Presente') {
                        $validado['estado'] = 'Retraso';
                    }
                } else {
                    $validado['estado_asistencia'] = 'PUNTUAL';
                    if (($validado['estado'] ?? $asistencia->estado) === 'Retraso') {
                        $validado['estado'] = 'Presente';
                    }
                }
            }

            if (!empty($horaEntrada) && !empty($horaSalida)) {
                $entrada = \Carbon\Carbon::createFromFormat('H:i:s', $horaEntrada);
                $salida = \Carbon\Carbon::createFromFormat('H:i:s', $horaSalida);
                $validado['horas_trabajadas'] = round($entrada->diffInMinutes($salida) / 60, 2);
            } else {
                $validado['horas_trabajadas'] = null;
            }

            $asistencia->update($validado);
            $asistencia->load(['empleado.departamento', 'empleado.cargo']);

            return $this->respuestaExito($asistencia, 'Asistencia actualizada correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Actualiza manualmente las horas extras y observación de un registro de asistencia.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function actualizarHorasExtras(Request $request, $id): JsonResponse
    {
        try {
            $validado = $request->validate([
                'horas_extras' => 'required|numeric|min:0',
                'observacion'  => 'nullable|string|max:255',
            ], [
                'horas_extras.required' => 'Las horas extras son obligatorias.',
                'horas_extras.numeric'  => 'Las horas extras deben ser un valor numérico.',
                'horas_extras.min'      => 'Las horas extras no pueden ser negativas.',
            ]);

            $asistencia = Asistencia::findOrFail($id);

            // Registro de log
            $usuarioActual = $request->user() ? $request->user()->id_usuario : 'Desconocido';
            \Illuminate\Support\Facades\Log::info("El usuario {$usuarioActual} actualizó las horas extras del registro de asistencia {$id}. Anterior: {$asistencia->horas_extras}, Nuevo: {$validado['horas_extras']}. Observación: {$validado['observacion']}");

            $asistencia->update([
                'horas_extras' => $validado['horas_extras'],
                'observacion'  => $validado['observacion'],
            ]);

            return $this->respuestaExito($asistencia, 'Horas extras actualizadas correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al actualizar horas extras: ' . $e->getMessage());
            return $this->respuestaServidor();
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $asistencia = Asistencia::findOrFail($id);
            $asistencia->delete();
            return $this->respuestaEliminado('Registro de asistencia eliminado correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }



    public function historialPorEmpleado(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'id_empleado' => 'required|integer',
                'mes' => 'required|integer|min:1|max:12',
                'anio' => 'required|integer|min:2000'
            ]);

            $idEmpleado = $request->id_empleado;
            $mes = $request->mes;
            $anio = $request->anio;

            $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfDay();
            $fechaFin = $fechaInicio->copy()->endOfMonth();

            $registros = Asistencia::where('id_empleado', $idEmpleado)
                ->whereBetween('fecha', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
                ->get()
                ->keyBy('fecha');

            $empleado = Empleado::find($idEmpleado);
            $nombreEmpleado = $empleado ? "{$empleado->nombre} {$empleado->apellido}" : 'Desconocido';

            $historial = [];
            $totales = [
                'puntuales' => 0,
                'retrasos' => 0,
                'faltas' => 0
            ];

            for ($dia = 1; $dia <= $fechaFin->daysInMonth; $dia++) {
                $fechaActual = Carbon::createFromDate($anio, $mes, $dia)->toDateString();
                
                if ($registros->has($fechaActual)) {
                    $registro = $registros->get($fechaActual);
                    $estado = strtolower($registro->estado_asistencia ?? $registro->estado);
                    
                    $historial[] = [
                        'fecha' => $fechaActual,
                        'hora_entrada' => $registro->hora_entrada ?? '--:--',
                        'hora_salida' => $registro->hora_salida ?? '--:--',
                        'estado_asistencia' => $estado
                    ];

                    if ($estado === 'puntual' || $estado === 'presente') {
                        $totales['puntuales']++;
                    } elseif ($estado === 'retraso' || $estado === 'tarde') {
                        $totales['retrasos']++;
                    } else {
                        $totales['faltas']++;
                    }
                } else {
                    $historial[] = [
                        'fecha' => $fechaActual,
                        'hora_entrada' => '--:--',
                        'hora_salida' => '--:--',
                        'estado_asistencia' => 'falta'
                    ];
                    $totales['faltas']++;
                }
            }

            return $this->respuestaExito([
                'empleado' => $nombreEmpleado,
                'mes_anio' => "{$mes}/{$anio}",
                'totales' => $totales,
                'historial' => $historial
            ], 'Historial de asistencia generado correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            Log::error('Error en historialPorEmpleado: ' . $e->getMessage());
            return $this->respuestaServidor();
        }
    }

    public function registrarAsistenciaFacial(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'latitud'     => 'required|numeric',
                'longitud'    => 'required|numeric',
                'id_empleado' => 'required|integer',
            ]);

            $idEmpleado = $request->id_empleado;

            // ── Leer configuraciones desde la BD ──────────────────────────────
            $validarUbicacion  = Configuracion::getBool('validacion_ubicacion', true);
            $empresaLat        = Configuracion::getDecimal('empresa_lat', -17.394291);
            $empresaLon        = Configuracion::getDecimal('empresa_lon', -66.074135);
            $radioPermitido    = Configuracion::getInt('radio_asistencia', 500);
            $horaEntradaConfig = Configuracion::getString('hora_entrada', '08:00');
            $toleranciaMin     = Configuracion::getInt('tolerancia_minutos', 0);

            // ── Validación de ubicación (solo si está activada) ───────────────
            $distancia = $this->calcularDistancia(
                $empresaLat, $empresaLon,
                $request->latitud, $request->longitud
            );

            if ($validarUbicacion && $distancia > $radioPermitido) {
                $this->registrarLogReconocimiento(
                    $idEmpleado,
                    'RECONOCIMIENTO_FALLIDO',
                    "Intento fuera de rango geográfico ({$distancia}m > {$radioPermitido}m)"
                );

                return response()->json([
                    'success'          => false,
                    'message'          => "Estás fuera del rango permitido ({$radioPermitido} m) para registrar asistencia.",
                    'distancia_metros' => round($distancia, 2),
                    'radio_permitido'  => $radioPermitido,
                ], 403);
            }

            $fechaHoy   = Carbon::now()->toDateString();
            $horaActual = Carbon::now()->toTimeString();

            $asistenciaHoy = DB::table('asistencia')
                ->where('id_empleado', $idEmpleado)
                ->where('fecha', $fechaHoy)
                ->first();

            if (! $asistenciaHoy) {
                // ── Determinar estado (PUNTUAL / TARDE) con hora y tolerancia de config
                [$h, $m] = explode(':', $horaEntradaConfig);
                $horaLimite = Carbon::today()->setTime((int) $h, (int) $m, 0)
                    ->addMinutes($toleranciaMin);

                if (Carbon::now()->greaterThan($horaLimite)) {
                    $estadoAsistencia = 'TARDE';
                    $estadoGeneral    = 'Retraso';
                } else {
                    $estadoAsistencia = 'PUNTUAL';
                    $estadoGeneral    = 'Presente';
                }

                DB::table('asistencia')->insert([
                    'id_empleado'      => $idEmpleado,
                    'fecha'            => $fechaHoy,
                    'hora_entrada'     => $horaActual,
                    'metodo_registro'  => 'Facial',
                    'estado'           => $estadoGeneral,
                    'estado_asistencia'=> $estadoAsistencia,
                    'latitud'          => $request->latitud,
                    'longitud'         => $request->longitud,
                    'distancia'        => $distancia,
                    'created_at'       => Carbon::now(),
                    'updated_at'       => Carbon::now(),
                ]);

                $mensaje = "Hora de entrada registrada correctamente ({$estadoAsistencia}).";

            } elseif (is_null($asistenciaHoy->hora_salida)) {
                $horaEntrada     = Carbon::parse($asistenciaHoy->hora_entrada);
                $horasTrabajadas = $horaEntrada->diffInMinutes(Carbon::now()) / 60;

                DB::table('asistencia')
                    ->where('id_asistencia', $asistenciaHoy->id_asistencia)
                    ->update([
                        'hora_salida'      => $horaActual,
                        'horas_trabajadas' => round($horasTrabajadas, 2),
                        'updated_at'       => Carbon::now(),
                    ]);

                $mensaje = 'Hora de salida registrada correctamente.';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya has completado tu registro de entrada y salida por hoy.',
                ], 400);
            }

            $this->registrarLogReconocimiento($idEmpleado, 'RECONOCIMIENTO_EXITOSO', $mensaje);

            return response()->json([
                'success'          => true,
                'message'          => $mensaje,
                'distancia_metros' => round($distancia, 2),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            Log::error('Error en registrarAsistenciaFacial: ' . $e->getMessage());
            $this->registrarLogReconocimiento(
                $request->id_empleado ?? 0,
                'RECONOCIMIENTO_FALLIDO',
                'Error interno del servidor'
            );
            return $this->respuestaServidor();
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

    public function exportarExcel(Request $request)
    {
        try {
            $consulta = Asistencia::with(['empleado.departamento', 'empleado.cargo']);

            // Aplicar filtros similares al index()
            if ($request->filled('fecha')) {
                $consulta->whereDate('fecha', $request->fecha);
            }
            if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
                $consulta->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
            }
            if ($request->filled('id_empleado') || $request->filled('empleado_id')) {
                $consulta->where('id_empleado', $request->input('id_empleado') ?? $request->input('empleado_id'));
            }
            if ($request->filled('estado')) {
                $consulta->where('estado', $request->estado);
            }
            
            $asistencias = $consulta->orderBy('fecha', 'desc')->get();

            // Preparar los datos
            $datos = [];
            $totalHoras = 0;

            foreach ($asistencias as $index => $asistencia) {
                $datos[] = [
                    'Nro' => $index + 1,
                    'Empleado' => $asistencia->empleado ? $asistencia->empleado->nombre . ' ' . $asistencia->empleado->apellido : 'N/A',
                    'Departamento' => $asistencia->empleado && $asistencia->empleado->departamento ? $asistencia->empleado->departamento->nombre : 'N/A',
                    'Fecha' => Carbon::parse($asistencia->fecha)->format('d/m/Y'),
                    'Entrada' => $asistencia->hora_entrada ?? '--:--',
                    'Salida' => $asistencia->hora_salida ?? '--:--',
                    'Horas Trabajadas' => $asistencia->horas_trabajadas ?? 0,
                    'Estado' => $asistencia->estado,
                ];
                $totalHoras += (float)($asistencia->horas_trabajadas ?? 0);
            }

            // Agregar fila de totales al final
            $datos[] = [
                'Total',
                '',
                '',
                '',
                '',
                '',
                $totalHoras,
                ''
            ];

            // Configurar Cabeceras
            $cabeceras = ['Nro', 'Empleado', 'Departamento', 'Fecha', 'Entrada', 'Salida', 'Horas Trabajadas', 'Estado'];
            
            // Título dinámico basado en filtros
            $titulo = "REPORTE DE ASISTENCIA GENERAL";
            if ($request->filled('fecha')) {
                $titulo = "REPORTE DE ASISTENCIA DEL " . Carbon::parse($request->fecha)->format('d/m/Y');
            } elseif ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
                $titulo = "REPORTE DE ASISTENCIA (" . Carbon::parse($request->fecha_inicio)->format('d/m/Y') . " AL " . Carbon::parse($request->fecha_fin)->format('d/m/Y') . ")";
            }

            $export = new DynamicReportExport(
                $datos, 
                $cabeceras, 
                $titulo, 
                'Tu Empresa S.A.' // Nombre de empresa
            );

            return Excel::download($export, 'reporte_asistencia_' . date('Ymd_His') . '.xlsx');

        } catch (\Exception $e) {
            Log::error('Error al exportar a Excel: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al generar el reporte Excel'], 500);
        }
    }
}
