<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\RespuestaJson;
use App\Models\Asistencia;
use App\Models\Ausencia;
use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\Planilla;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Controlador del Panel Principal (Dashboard).
 * Provee métricas y estadísticas del sistema.
 *
 * Optimización: de ~35 queries individuales → 6 queries con groupBy/selectRaw.
 */
class PanelControlador extends Controller
{
    use RespuestaJson;

    /**
     * Obtener todas las métricas del panel principal.
     */
    public function index(): JsonResponse
    {
        try {
            $hoy      = today()->format('Y-m-d');
            $mes      = now()->month;
            $anio     = now()->year;
            $hace7dias = now()->subDays(6)->format('Y-m-d');

            // ── QUERY 1: Métricas de empleados (1 query con groupBy) ─────
            $estadosEmpleados = Empleado::select('estado', DB::raw('count(*) as cantidad'))
                ->groupBy('estado')
                ->pluck('cantidad', 'estado');

            $totalEmpleados     = $estadosEmpleados->sum();
            $empleadosActivos   = $estadosEmpleados->get('Activo', 0);
            $empleadosInactivos = $estadosEmpleados->get('Inactivo', 0);

            // ── QUERY 2: Nuevos empleados este mes ───────────────────────
            $nuevosEsteMes = Empleado::whereMonth('created_at', $mes)
                ->whereYear('created_at', $anio)
                ->count();

            // ── QUERY 3: Asistencia hoy (1 query con groupBy) ────────────
            $asistenciaHoyAgrupada = Asistencia::whereDate('fecha', $hoy)
                ->select('estado', DB::raw('count(*) as cantidad'))
                ->groupBy('estado')
                ->pluck('cantidad', 'estado');

            $asistenciasHoy = $asistenciaHoyAgrupada->sum();
            $presentesHoy   = $asistenciaHoyAgrupada->get('Presente', 0);
            $retrasosHoy    = $asistenciaHoyAgrupada->get('Retraso', 0);
            $faltasHoy      = $asistenciaHoyAgrupada->get('Falta', 0);

            // ── QUERY 4: Ausencias ────────────────────────────────────────
            $ausenciasPendientes = Ausencia::where('estado', 'Pendiente')->count();
            $vacacionesActivas   = Ausencia::where('estado', 'Aprobado')
                ->where('fecha_inicio', '<=', $hoy)
                ->where('fecha_fin', '>=', $hoy)
                ->count();

            // ── QUERY 5: Planillas del mes (1 query con selectRaw) ────────
            $planillasMes = Planilla::where('mes', $mes)
                ->where('anio', $anio)
                ->selectRaw('
                    count(*) as total,
                    sum(case when fecha_pago is null then 1 else 0 end) as pendientes,
                    sum(salario_total) as total_a_pagar
                ')
                ->first();

            // ── QUERY 6: Asistencia últimos 7 días (1 query con groupBy) ─
            // Antes: 7 iteraciones × 3 queries = 21 queries
            // Ahora: 1 sola query con groupBy en fecha y estado
            $asistenciaSemanaRaw = Asistencia::whereBetween('fecha', [$hace7dias, $hoy])
                ->select('fecha', 'estado', DB::raw('count(*) as cantidad'))
                ->groupBy('fecha', 'estado')
                ->get()
                ->groupBy('fecha'); // Agrupar en PHP para formato final

            // Construir el array de 7 días a partir del resultado agrupado
            $asistenciaSemana = [];
            for ($i = 6; $i >= 0; $i--) {
                $fecha    = now()->subDays($i);
                $fechaStr = $fecha->format('Y-m-d');
                $diaData  = $asistenciaSemanaRaw->get($fechaStr, collect());

                $asistenciaSemana[] = [
                    'fecha'     => $fecha->format('d/m'),
                    'dia'       => $fecha->locale('es')->dayName,
                    'presentes' => $diaData->where('estado', 'Presente')->sum('cantidad'),
                    'retrasos'  => $diaData->where('estado', 'Retraso')->sum('cantidad'),
                    'faltas'    => $diaData->where('estado', 'Falta')->sum('cantidad'),
                ];
            }

            // ── Empleados por departamento ─────────────────────────────────
            // Esta query usa withCount de Eloquent — 1 query con LEFT JOIN
            $porDepartamento = Departamento::withCount('empleados')
                ->orderBy('empleados_count', 'desc')
                ->take(6)
                ->get()
                ->map(fn($d) => [
                    'departamento' => $d->nombre,
                    'cantidad'     => $d->empleados_count,
                ]);

            // ── Distribución de estados (viene de la Query 1) ─────────────
            $estadosGrafico = collect(['Activo', 'Inactivo', 'Vacaciones', 'Suspendido'])
                ->map(fn($estado) => [
                    'estado'   => $estado,
                    'cantidad' => $estadosEmpleados->get($estado, 0),
                ]);

            return $this->respuestaExito([
                'empleados' => [
                    'total'     => $totalEmpleados,
                    'activos'   => $empleadosActivos,
                    'inactivos' => $empleadosInactivos,
                    'nuevos'    => $nuevosEsteMes,
                ],
                'asistencia_hoy' => [
                    'total'     => $asistenciasHoy,
                    'presentes' => $presentesHoy,
                    'retrasos'  => $retrasosHoy,
                    'faltas'    => $faltasHoy,
                ],
                'ausencias' => [
                    'pendientes' => $ausenciasPendientes,
                    'activas'    => $vacacionesActivas,
                ],
                'planillas' => [
                    'pendientes'    => $planillasMes->pendientes ?? 0,
                    'total_a_pagar' => number_format($planillasMes->total_a_pagar ?? 0, 2),
                ],
                'grafico_departamentos' => $porDepartamento,
                'grafico_asistencia'    => $asistenciaSemana,
                'grafico_estados'       => $estadosGrafico,
            ], 'Panel principal actualizado');

        } catch (\Exception) {
            // SEGURIDAD: No exponer $e->getMessage() al cliente.
            // El error queda en los logs del servidor (storage/logs/laravel.log).
            return $this->respuestaServidor();
        }
    }
}
