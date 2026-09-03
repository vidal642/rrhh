<?php

namespace App\Services;

use App\Models\Adelanto;
use App\Models\Asistencia;
use App\Models\Configuracion;
use App\Models\Empleado;
use Carbon\Carbon;

class PlanillaCalculadoraServicio
{
 
    const FACTOR_HORAS_EXTRA = 1.5;

    const HORAS_JORNADA = 8;

    /**
     * @param  array $datos
     * @return array
     */

    public function calcularDesdeDatos(array $datos): array
    {
        $salarioBase           = (float) ($datos['salario_base'] ?? 0);
        $bonos                 = (float) ($datos['bonos'] ?? 0);
        $horasExtra            = (float) ($datos['horas_extra'] ?? 0);
        $descuentosAutomaticos = (float) ($datos['descuentos_automaticos'] ?? 0);
        $idEmpleado            = $datos['id_empleado'];

        $totalDescuentosManuales = 0.0;
        foreach ($datos['descuentos_manuales'] ?? [] as $dm) {
            $totalDescuentosManuales += (float) $dm['monto'];
        }

        $adelantosPendientes     = $this->obtenerAdelantosPendientes($idEmpleado);
        $adelantosAplicadosMonto = $adelantosPendientes->sum('monto');

        $totalDescuentos = $descuentosAutomaticos + $totalDescuentosManuales + $adelantosAplicadosMonto;
        $salarioTotal    = $salarioBase + $bonos + $horasExtra - $totalDescuentos;

        return [
            'salario_total'        => $salarioTotal,
            'descuentos'           => $totalDescuentos,
            'adelantos_aplicados'  => $adelantosAplicadosMonto,
            'adelantos_pendientes' => $adelantosPendientes,
            'es_valido'            => $salarioTotal >= 0,
        ];
    }

    /**
     * Calcula todos los datos de planilla para un empleado a partir de
     * sus registros de asistencia del período indicado.
     *
     * @param  Empleado $empleado
     * @param  int      $mes
     * @param  int      $anio
     * @return array    Array completo listo para Planilla::create()
     */
    public function calcularDesdeAsistencia(Empleado $empleado, int $mes, int $anio, $asistenciasPreCargadas = null, $adelantosPreCargados = null): array
    {
        $inicio = Carbon::createFromDate($anio, $mes, 1)->startOfDay();
        $fin    = Carbon::createFromDate($anio, $mes, 1)->endOfMonth()->endOfDay();

        if ($asistenciasPreCargadas !== null) {
            $asistencias = $asistenciasPreCargadas;
        } else {
            $asistencias = Asistencia::where('id_empleado', $empleado->id_empleado)
                ->whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
                ->get();
        }

        // ── Leer configuraciones de planillas ──────────────────────────────
        $aplicarAdelantos   = Configuracion::getBool('aplicacion_automatica_adelantos', true);
        $aplicarDescuentos  = Configuracion::getBool('aplicacion_automatica_descuentos', true);
        $calcularHoras      = Configuracion::getBool('calculo_automatico_horas', true);

        // ── Obtener Ausencias Aprobadas (Vacaciones y Permisos) ───────────────
        $ausenciasAprobadas = \App\Models\Ausencia::where('id_empleado', $empleado->id_empleado)
            ->where('estado', 'Aprobado')
            ->whereIn('tipo', ['Vacación', 'Permiso'])
            ->where(function($q) use ($inicio, $fin) {
                $q->whereBetween('fecha_inicio', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
                  ->orWhereBetween('fecha_fin', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
                  ->orWhere(function($q2) use ($inicio, $fin) {
                      $q2->where('fecha_inicio', '<=', $inicio->format('Y-m-d'))
                         ->where('fecha_fin', '>=', $fin->format('Y-m-d'));
                  });
            })
            ->get();

        // ── Conteo de días y Faltas Injustificadas ──────────────────────────
        $diasPresentes  = $asistencias->whereIn('estado', ['Presente', 'Permiso', 'Vacación'])->count();
        $diasRetraso    = $asistencias->where('estado', 'Retraso')->count();
        $diasTrabajados = $diasPresentes + $diasRetraso;

        $faltasInjustificadas = 0;
        $diasMes = $inicio->daysInMonth;
        
        for ($dia = 1; $dia <= $diasMes; $dia++) {
            $fechaActual = \Carbon\Carbon::createFromDate($anio, $mes, $dia);
            
            // Si la fecha es en el futuro (ej. calculando a mitad de mes), no es falta
            if ($fechaActual->isFuture()) {
                continue;
            }

            // Excluir domingos de la evaluación de faltas
            if ($fechaActual->dayOfWeek === \Carbon\Carbon::SUNDAY) {
                continue;
            }

            $fechaStr = $fechaActual->format('Y-m-d');
            
            // 1. Verificar si hay asistencia registrada
            $tieneAsistencia = $asistencias->contains(function ($asistencia) use ($fechaStr) {
                return \Carbon\Carbon::parse($asistencia->fecha)->format('Y-m-d') === $fechaStr 
                       && in_array($asistencia->estado, ['Presente', 'Retraso', 'Permiso', 'Vacación']);
            });

            if ($tieneAsistencia) {
                continue; // No es falta
            }

            // 2. Verificar si está cubierto por vacación o permiso aprobado
            $estaCubierto = $ausenciasAprobadas->contains(function ($ausencia) use ($fechaStr) {
                return $fechaStr >= $ausencia->fecha_inicio && $fechaStr <= $ausencia->fecha_fin;
            });

            if ($estaCubierto) {
                continue; // No es falta
            }

            // Sin asistencia y sin justificación -> Falta Injustificada
            $faltasInjustificadas++;
        }

        // ── Horas ───────────────────────────────────────────────
        $horasTrabajadas    = (float) $asistencias
            ->whereIn('estado', ['Presente', 'Retraso', 'Permiso'])
            ->sum('horas_trabajadas');
        $horasNormales      = $diasTrabajados * self::HORAS_JORNADA;

        // Sumar siempre las horas extra manuales aprobadas
        $horasExtraCantidad = (float) $asistencias->sum('horas_extras');

        // ── Montos ─────────────────────────────────────────────
        $salarioBase        = (float) $empleado->salario_base;
        $pagoHora           = $this->calcularPagoHora($salarioBase, $diasMes);
        $montoHorasExtra    = round($horasExtraCantidad * $pagoHora * self::FACTOR_HORAS_EXTRA, 2);

        $pagoDia             = $salarioBase > 0 ? round($salarioBase / $diasMes, 2) : 0;
        // Siempre aplicar descuento por las faltas injustificadas calculadas
        $descuentoAutomatico = $faltasInjustificadas * $pagoDia;

        // ── Adelantos ───────────────────────────────────────────
        // Solo aplicar adelantos si la configuración lo permite
        if ($aplicarAdelantos) {
            if ($adelantosPreCargados !== null) {
                $adelantosPendientes = $adelantosPreCargados;
            } else {
                $adelantosPendientes = $this->obtenerAdelantosPendientes($empleado->id_empleado);
            }
            $adelantosAplicadosMonto = $adelantosPendientes->sum('monto');
        } else {
            $adelantosPendientes     = collect();
            $adelantosAplicadosMonto = 0;
        }

        $totalDescuentos = $descuentoAutomatico + $adelantosAplicadosMonto;
        $salarioTotal    = max(0, $salarioBase + $montoHorasExtra - $totalDescuentos);

        return [
            // Campos para Planilla::create()
            'id_empleado'            => $empleado->id_empleado,
            'mes'                    => $mes,
            'anio'                   => $anio,
            'salario_base'           => $salarioBase,
            'bonos'                  => 0,
            'horas_extra'            => $montoHorasExtra,
            'horas_extra_cantidad'   => $horasExtraCantidad,
            'descuentos_automaticos' => $descuentoAutomatico,
            'adelantos_aplicados'    => $adelantosAplicadosMonto,
            'descuentos'             => $totalDescuentos,
            'salario_total'          => $salarioTotal,
            'dias_trabajados'        => $diasTrabajados,
            'horas_trabajadas_total' => round($horasTrabajadas, 2),
            // Metadatos del cálculo (no se persisten directamente)
            'dias_del_mes'           => $diasMes,
            'adelantos_pendientes'   => $adelantosPendientes,
            'faltas'                 => $faltasInjustificadas,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Método auxiliar: también usado por PlanillaControlador::calcularAsistencia()
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Calcula el pago por hora basado en el salario base y los días reales del mes.
     * Fórmula: salario_base / dias_mes / horas_jornada
     */
    public function calcularPagoHora(float $salarioBase, int $diasMes): float
    {
        return $salarioBase > 0
            ? round($salarioBase / $diasMes / self::HORAS_JORNADA, 4)
            : 0;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Obtiene los adelantos pendientes de un empleado.
     * Extraído como método privado para reutilización interna.
     */
    private function obtenerAdelantosPendientes(int $idEmpleado)
    {
        return Adelanto::where('empleado_id', $idEmpleado)
            ->where('estado', 'Aprobado')
            ->get();
    }
}
