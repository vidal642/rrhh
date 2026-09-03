<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\RespuestaJson;
use App\Models\Adelanto;
use App\Models\Asistencia;
use App\Models\DescuentoManual;
use App\Models\Empleado;
use App\Models\Planilla;
use App\Services\PlanillaCalculadoraServicio;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanillaControlador extends Controller
{
    use RespuestaJson;

    public function __construct(
        private readonly PlanillaCalculadoraServicio $calculadora
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        try {
            $consulta = Planilla::with(['empleado.departamento', 'empleado.cargo', 'descuentosManuales', 'adelantos']);

            if ($request->filled('mes')) {
                $consulta->where('mes', $request->mes);
            }
            if ($request->filled('anio')) {
                $consulta->where('anio', $request->anio);
            }
            // Acepta tanto id_empleado como empleado_id para compatibilidad con frontend
            if ($request->filled('id_empleado') || $request->filled('empleado_id')) {
                $consulta->where('id_empleado', $request->input('id_empleado') ?? $request->input('empleado_id'));
            }

            if ($request->filled('estado')) {
                match ($request->estado) {
                    'Pagado'   => $consulta->whereNotNull('fecha_pago'),
                    'Pendiente' => $consulta->whereNull('fecha_pago'),
                    default    => null,
                };
            }

            if ($request->filled('id_departamento') || $request->filled('departamento_id')) {
                $consulta->whereHas('empleado', function ($q) use ($request) {
                    $q->where('id_departamento', $request->input('id_departamento') ?? $request->input('departamento_id'));
                });
            }

            if ($request->filled('q')) {
                $termino = $request->q;
                $consulta->whereHas('empleado', function ($q) use ($termino) {
                    $q->where('nombre', 'like', '%' . $termino . '%')
                      ->orWhere('apellido', 'like', '%' . $termino . '%')
                      ->orWhere(DB::raw("CONCAT(nombre, ' ', apellido)"), 'like', '%' . $termino . '%');
                });
            }

            $perPage = $request->input('per_page', 10);
            if ($perPage === 'all' || $perPage == -1) {
                $planillas = $consulta->orderBy('anio', 'desc')->orderBy('mes', 'desc')->get();
                $planillas->transform(function ($p) {
                    if (!$p->relationLoaded('descuentosManuales')) $p->setRelation('descuentosManuales', collect());
                    if (!$p->relationLoaded('adelantos')) $p->setRelation('adelantos', collect());
                    return $this->agregarEstado($p);
                });
                $datos = [
                    'data' => $planillas,
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => $planillas->count()
                ];
            } else {
                $planillasPag = $consulta->orderBy('anio', 'desc')->orderBy('mes', 'desc')->paginate($perPage);
                $planillasPag->getCollection()->transform(function ($p) {
                    if (!$p->relationLoaded('descuentosManuales')) $p->setRelation('descuentosManuales', collect());
                    if (!$p->relationLoaded('adelantos')) $p->setRelation('adelantos', collect());
                    return $this->agregarEstado($p);
                });
                $datos = [
                    'data' => $planillasPag->items(),
                    'current_page' => $planillasPag->currentPage(),
                    'last_page' => $planillasPag->lastPage(),
                    'total' => $planillasPag->total()
                ];
            }

            return $this->respuestaExito($datos, 'Planillas obtenidas correctamente');
        } catch (\Exception) {
            return $this->respuestaServidor();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STORE — usa PlanillaCalculadoraServicio::calcularDesdeDatos()
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        try {
            $validado = $request->validate(
                $this->reglasValidacion(),
                $this->mensajesValidacion()
            );

            $existe = Planilla::where('id_empleado', $validado['id_empleado'])
                ->where('mes', $validado['mes'])
                ->where('anio', $validado['anio'])
                ->exists();

            if ($existe) {
                return $this->respuestaError('Ya existe una planilla para este empleado en el período indicado.', 409);
            }

            DB::beginTransaction();

            // ── Delegar todo el cálculo al servicio ──────────────────────
            $calculo = $this->calculadora->calcularDesdeDatos($validado);

            if (! $calculo['es_valido']) {
                DB::rollBack();
                return $this->respuestaError('Los descuentos superan el salario total del empleado.', 422);
            }

            $planilla = Planilla::create([
                ...$validado,
                'bonos'                  => $validado['bonos'] ?? 0,
                'horas_extra'            => $validado['horas_extra'] ?? 0,
                'descuentos_automaticos' => $validado['descuentos_automaticos'] ?? 0,
                'adelantos_aplicados'    => $calculo['adelantos_aplicados'],
                'descuentos'             => $calculo['descuentos'],
                'salario_total'          => $calculo['salario_total'],
            ]);

            // Persistir descuentos manuales
            foreach ($validado['descuentos_manuales'] ?? [] as $dm) {
                DescuentoManual::create([
                    'id_planilla' => $planilla->id_planilla,
                    'monto'       => $dm['monto'],
                    'descripcion' => $dm['descripcion'],
                    'fecha'       => $dm['fecha'],
                ]);
            }

            // Marcar adelantos como aplicados
            foreach ($calculo['adelantos_pendientes'] as $adelanto) {
                $adelanto->update(['planilla_id' => $planilla->id_planilla, 'estado' => 'Aplicado']);
            }

            DB::commit();

            $planilla->load(['empleado.departamento', 'empleado.cargo', 'descuentosManuales', 'adelantos']);
            return $this->respuestaCreado($this->agregarEstado($planilla), 'Planilla creada correctamente');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception) {
            DB::rollBack();
            return $this->respuestaServidor();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────────────────────────────────

    public function show($id, Request $request): JsonResponse
    {
        try {
            $planilla = Planilla::with(['empleado.departamento', 'empleado.cargo', 'descuentosManuales', 'adelantos'])
                ->findOrFail($id);

            // Validar seguridad: Solo el propio empleado o un administrador pueden ver la planilla
            $usuario = $request->user();
            $rol = strtolower($usuario->rol ?? '');
            if ($rol !== 'admin' && $rol !== 'administrador' && $usuario->id_empleado != $planilla->id_empleado) {
                return $this->respuestaError('No tiene permisos para ver esta planilla.', 403);
            }

            return $this->respuestaExito($this->agregarEstado($planilla), 'Planilla obtenida correctamente');
        } catch (\Exception) {
            return $this->respuestaServidor();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────────────────────────────────

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $planilla = Planilla::findOrFail($id);

            if ($planilla->fecha_pago) {
                return $this->respuestaError('No se puede modificar una planilla con estado "Pagado".', 409);
            }

            $validado = $request->validate(
                $this->reglasValidacion(esActualizacion: true),
                $this->mensajesValidacion()
            );

            DB::beginTransaction();

            $salarioBase           = $validado['salario_base'] ?? $planilla->salario_base;
            $bonos                 = $validado['bonos'] ?? $planilla->bonos;
            $horasExtra            = $validado['horas_extra'] ?? $planilla->horas_extra;
            $descuentosAutomaticos = $validado['descuentos_automaticos'] ?? $planilla->descuentos_automaticos;

            if (isset($validado['descuentos_manuales'])) {
                DescuentoManual::where('id_planilla', $planilla->id_planilla)->delete();
                $totalDescuentosManuales = 0;
                foreach ($validado['descuentos_manuales'] as $dm) {
                    DescuentoManual::create([
                        'id_planilla' => $planilla->id_planilla,
                        'monto'       => $dm['monto'],
                        'descripcion' => $dm['descripcion'],
                        'fecha'       => $dm['fecha'],
                    ]);
                    $totalDescuentosManuales += $dm['monto'];
                }
            } else {
                $totalDescuentosManuales = $planilla->descuentosManuales()->sum('monto');
            }

            $totalDescuentos = $descuentosAutomaticos + $totalDescuentosManuales + $planilla->adelantos_aplicados;
            $salarioTotal    = $salarioBase + $bonos + $horasExtra - $totalDescuentos;

            if ($salarioTotal < 0) {
                DB::rollBack();
                return $this->respuestaError('Los descuentos superan el salario total del empleado.', 422);
            }

            $planilla->update([
                ...$validado,
                'salario_total' => $salarioTotal,
                'descuentos'    => $totalDescuentos,
            ]);

            DB::commit();

            $planilla->load(['empleado.departamento', 'empleado.cargo', 'descuentosManuales', 'adelantos']);
            return $this->respuestaExito($this->agregarEstado($planilla), 'Planilla actualizada correctamente');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception) {
            DB::rollBack();
            return $this->respuestaServidor();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DESTROY
    // ─────────────────────────────────────────────────────────────────────────

    public function destroy($id): JsonResponse
    {
        try {
            $planilla = Planilla::findOrFail($id);
            if ($planilla->fecha_pago) {
                return $this->respuestaError('No se puede eliminar una planilla con estado "Pagado".', 409);
            }

            DB::beginTransaction();
            Adelanto::where('planilla_id', $planilla->id_planilla)->update([
                'planilla_id' => null,
                'estado'      => 'Pendiente',
            ]);
            $planilla->delete();
            DB::commit();

            return $this->respuestaEliminado('Planilla eliminada correctamente');
        } catch (\Exception) {
            DB::rollBack();
            return $this->respuestaServidor();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MARCAR PAGADO
    // ─────────────────────────────────────────────────────────────────────────

    public function marcarPagado($id): JsonResponse
    {
        try {
            $planilla = Planilla::findOrFail($id);
            if ($planilla->fecha_pago) {
                return $this->respuestaError('La planilla ya está marcada como pagada.', 409);
            }

            $planilla->update(['fecha_pago' => now()->format('Y-m-d')]);
            return $this->respuestaExito($this->agregarEstado($planilla), 'Planilla marcada como pagada correctamente');
        } catch (\Exception) {
            return $this->respuestaServidor();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RESUMEN MENSUAL
    // ─────────────────────────────────────────────────────────────────────────

    public function resumenMensual(Request $request): JsonResponse
    {
        try {
            $mes  = $request->get('mes', now()->month);
            $anio = $request->get('anio', now()->year);

            $planillas = Planilla::where('mes', $mes)->where('anio', $anio)->get();

            return $this->respuestaExito([
                'mes'             => (int) $mes,
                'anio'            => (int) $anio,
                'total_planillas' => $planillas->count(),
                'pendientes'      => $planillas->whereNull('fecha_pago')->count(),
                'pagadas'         => $planillas->whereNotNull('fecha_pago')->count(),
                'anuladas'        => 0,
                'total_bonos'     => $planillas->sum('bonos'),
                'total_descuentos'=> $planillas->sum('descuentos'),
                'total_pagar'     => $planillas->sum('salario_total'),
                'total_bruto'     => $planillas->sum('salario_base') + $planillas->sum('bonos') + $planillas->sum('horas_extra'),
                'total_neto'      => $planillas->sum('salario_total'),
            ], 'Resumen mensual obtenido correctamente');
        } catch (\Exception) {
            return $this->respuestaServidor();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CALCULAR ASISTENCIA — ahora usa PlanillaCalculadoraServicio
    // ─────────────────────────────────────────────────────────────────────────

    public function calcularAsistencia(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'id_empleado' => 'required|exists:empleado,id_empleado',
                'mes'         => 'required|integer|between:1,12',
                'anio'        => 'required|integer|min:2000|max:2099',
            ], [
                'id_empleado.required' => 'Seleccione un empleado válido.',
                'id_empleado.exists'   => 'Seleccione un empleado válido.',
                'mes.required'         => 'El mes es obligatorio.',
                'mes.between'          => 'El mes ingresado no es válido.',
                'anio.required'        => 'El año es obligatorio.',
                'anio.min'             => 'El año ingresado no es válido.',
            ]);

            $empleado = Empleado::findOrFail($request->id_empleado);
            $mes      = (int) $request->mes;
            $anio     = (int) $request->anio;

            // Delegar al servicio — misma lógica, sin duplicación
            $calculo = $this->calculadora->calcularDesdeAsistencia($empleado, $mes, $anio);

            return $this->respuestaExito([
                'id_empleado'          => (int) $empleado->id_empleado,
                'mes'                  => $mes,
                'anio'                 => $anio,
                'dias_trabajados'      => $calculo['dias_trabajados'],
                'horas_trabajadas'     => $calculo['horas_trabajadas_total'],
                'horas_extra_cantidad' => $calculo['horas_extra_cantidad'],
                'salario_base'         => $calculo['salario_base'],
                'pago_por_hora'        => $this->calculadora->calcularPagoHora($calculo['salario_base'], $calculo['dias_del_mes']),
                'monto_horas_extra'    => $calculo['horas_extra'],
                'descuento_automatico' => $calculo['descuentos_automaticos'],
                'faltas'               => $calculo['faltas'],
                'dias_del_mes'         => $calculo['dias_del_mes'],
                'adelantos_pendientes' => $calculo['adelantos_pendientes'],
                'total_adelantos'      => $calculo['adelantos_aplicados'],
            ], 'Datos de asistencia calculados correctamente');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception) {
            return $this->respuestaServidor();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GENERAR PLANILLA MENSUAL — usa PlanillaCalculadoraServicio::calcularDesdeAsistencia()
    // ─────────────────────────────────────────────────────────────────────────

    public function generarPlanillaMensual(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'mes'  => 'required|integer|between:1,12',
                'anio' => 'required|integer|min:2000|max:2099',
            ], [
                'mes.required'  => 'El mes es obligatorio.',
                'mes.between'   => 'El mes ingresado no es válido.',
                'anio.required' => 'El año es obligatorio.',
                'anio.min'      => 'El año ingresado no es válido.',
            ]);

            $mes  = (int) $request->mes;
            $anio = (int) $request->anio;

            $empleados = Empleado::where('estado', 'Activo')->get();
            $empleadosIds = $empleados->pluck('id_empleado')->toArray();

            // PRECARGA MASIVA PARA EVITAR N+1
            $inicio = \Carbon\Carbon::createFromDate($anio, $mes, 1)->startOfDay();
            $fin    = \Carbon\Carbon::createFromDate($anio, $mes, 1)->endOfMonth()->endOfDay();

            $todasLasAsistencias = Asistencia::whereIn('id_empleado', $empleadosIds)
                ->whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
                ->get()
                ->groupBy('id_empleado');

            $todosLosAdelantos = \App\Models\Adelanto::whereIn('empleado_id', $empleadosIds)
                ->where('estado', 'Aprobado')
                ->get()
                ->groupBy('empleado_id');

            // Precarga de planillas existentes
            $planillasExistentes = Planilla::whereIn('id_empleado', $empleadosIds)
                ->where('mes', $mes)
                ->where('anio', $anio)
                ->pluck('id_empleado')
                ->toArray();

            $creadas  = 0;
            $omitidas = 0;
            $errores  = [];
            $detalle  = [];

            foreach ($empleados as $empleado) {
                if (in_array($empleado->id_empleado, $planillasExistentes)) {
                    $omitidas++;
                    continue;
                }

                try {
                    DB::beginTransaction();

                    // Obtener las colecciones precargadas o vacías si no hay registros
                    $asistenciasEmpleado = $todasLasAsistencias->get($empleado->id_empleado, collect());
                    $adelantosEmpleado = $todosLosAdelantos->get($empleado->id_empleado, collect());

                    // ── Delegar cálculo al servicio ───────────────────────
                    $calculo = $this->calculadora->calcularDesdeAsistencia(
                        $empleado, 
                        $mes, 
                        $anio, 
                        $asistenciasEmpleado, 
                        $adelantosEmpleado
                    );

                    $planilla = Planilla::create([
                        'id_empleado'            => $calculo['id_empleado'],
                        'mes'                    => $calculo['mes'],
                        'anio'                   => $calculo['anio'],
                        'salario_base'           => $calculo['salario_base'],
                        'bonos'                  => $calculo['bonos'],
                        'horas_extra'            => $calculo['horas_extra'],
                        'horas_extra_cantidad'   => $calculo['horas_extra_cantidad'],
                        'descuentos_automaticos' => $calculo['descuentos_automaticos'],
                        'adelantos_aplicados'    => $calculo['adelantos_aplicados'],
                        'descuentos'             => $calculo['descuentos'],
                        'salario_total'          => $calculo['salario_total'],
                        'dias_trabajados'        => $calculo['dias_trabajados'],
                        'horas_trabajadas_total' => $calculo['horas_trabajadas_total'],
                    ]);

                    foreach ($calculo['adelantos_pendientes'] as $adelanto) {
                        $adelanto->update(['planilla_id' => $planilla->id_planilla, 'estado' => 'Aplicado']);
                    }

                    DB::commit();
                    $creadas++;
                    $detalle[] = [
                        'empleado'      => "{$empleado->nombre} {$empleado->apellido}",
                        'id_planilla'   => $planilla->id_planilla,
                        'salario_total' => $calculo['salario_total'],
                    ];
                } catch (\Exception $e) {
                    DB::rollBack();
                    $errores[] = [
                        'empleado' => "{$empleado->nombre} {$empleado->apellido}",
                        'error'    => 'Error al generar la planilla.',
                    ];
                }
            }

            return $this->respuestaExito([
                'mes'             => $mes,
                'anio'            => $anio,
                'creadas'         => $creadas,
                'omitidas'        => $omitidas,
                'errores'         => count($errores),
                'detalle'         => $detalle,
                'errores_detalle' => $errores,
            ], "Planilla mensual generada: {$creadas} creadas, {$omitidas} ya existían.");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception) {
            return $this->respuestaServidor();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos privados auxiliares
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Agrega el campo virtual 'estado' a una planilla.
     * DRY: evita repetir la ternaria en cada método.
     */
    private function agregarEstado(Planilla $planilla): Planilla
    {
        $planilla->estado = $planilla->fecha_pago ? 'Pagado' : 'Pendiente';
        return $planilla;
    }

    /**
     * Reglas de validación centralizadas para store y update.
     * DRY: un solo lugar para mantener las reglas.
     *
     * @param  bool $esActualizacion  Si true, usa 'sometimes' en lugar de 'required'
     */
    private function reglasValidacion(bool $esActualizacion = false): array
    {
        $req = $esActualizacion ? 'sometimes' : 'required';

        return [
            'id_empleado'                        => "{$req}|exists:empleado,id_empleado",
            'mes'                                => "{$req}|integer|between:1,12",
            'anio'                               => "{$req}|integer|min:2000|max:2099",
            'salario_base'                       => "{$req}|numeric|min:0",
            'bonos'                              => 'nullable|numeric|min:0',
            'horas_extra'                        => 'nullable|numeric|min:0',
            'fecha_pago'                         => 'nullable|date',
            'dias_trabajados'                    => 'nullable|numeric|min:0',
            'horas_trabajadas_total'             => 'nullable|numeric|min:0',
            'horas_extra_cantidad'               => 'nullable|numeric|min:0',
            'descuentos_automaticos'             => 'nullable|numeric|min:0',
            'descuentos_manuales'                => 'nullable|array',
            'descuentos_manuales.*.monto'        => 'required|numeric|min:0.01',
            'descuentos_manuales.*.descripcion'  => 'required|string',
            'descuentos_manuales.*.fecha'        => 'required|date',
        ];
    }

    /**
     * Mensajes de validación en español, reutilizados en store y update.
     */
    private function mensajesValidacion(): array
    {
        return [
            'id_empleado.required'               => 'Seleccione un empleado válido.',
            'id_empleado.exists'                 => 'Seleccione un empleado válido.',
            'mes.required'                       => 'El mes es obligatorio.',
            'mes.between'                        => 'El mes ingresado no es válido.',
            'anio.required'                      => 'El año es obligatorio.',
            'anio.min'                           => 'El año ingresado no es válido.',
            'anio.max'                           => 'El año ingresado no es válido.',
            'salario_base.required'              => 'El salario base es obligatorio.',
            'salario_base.min'                   => 'El salario base no puede ser negativo.',
            'bonos.min'                          => 'Los bonos no pueden ser negativos.',
            'horas_extra.min'                    => 'El monto de horas extra no puede ser negativo.',
            'fecha_pago.date'                    => 'La fecha de pago no es correcta.',
            'dias_trabajados.min'                => 'Los días trabajados no pueden ser negativos.',
            'horas_trabajadas_total.min'         => 'Las horas trabajadas no pueden ser negativas.',
            'horas_extra_cantidad.min'           => 'La cantidad de horas extra no puede ser negativa.',
            'descuentos_automaticos.min'         => 'Los descuentos automáticos no pueden ser negativos.',
            'descuentos_manuales.*.monto.min'    => 'El monto del descuento no puede ser negativo o cero.',
        ];
    }
}
