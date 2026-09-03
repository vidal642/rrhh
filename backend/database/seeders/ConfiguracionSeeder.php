<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfiguracionSeeder extends Seeder
{
    /**
     * Inserta o actualiza los valores iniciales de configuración del sistema.
     * Usa updateOrInsert para ser idempotente (seguro de ejecutar varias veces).
     */
    public function run(): void
    {
        $configuraciones = [

            // ─── ASISTENCIA ────────────────────────────────────────────────────────
            [
                'clave'       => 'hora_entrada',
                'valor'       => '08:00',
                'grupo'       => 'asistencia',
                'tipo'        => 'time',
                'descripcion' => 'Hora de entrada estándar (HH:MM)',
            ],
            [
                'clave'       => 'hora_salida',
                'valor'       => '17:00',
                'grupo'       => 'asistencia',
                'tipo'        => 'time',
                'descripcion' => 'Hora de salida estándar (HH:MM)',
            ],
            [
                'clave'       => 'tolerancia_minutos',
                'valor'       => '0',
                'grupo'       => 'asistencia',
                'tipo'        => 'integer',
                'descripcion' => 'Minutos de tolerancia permitidos antes de marcar retraso',
            ],
            [
                'clave'       => 'dias_laborales',
                'valor'       => 'Lunes a Sábado',
                'grupo'       => 'asistencia',
                'tipo'        => 'string',
                'descripcion' => 'Días laborales de la semana',
            ],
            [
                'clave'       => 'reconocimiento_facial',
                'valor'       => 'true',
                'grupo'       => 'asistencia',
                'tipo'        => 'boolean',
                'descripcion' => 'Activar control de asistencia por reconocimiento facial',
            ],
            [
                'clave'       => 'descuentos_retrasos',
                'valor'       => 'false',
                'grupo'       => 'asistencia',
                'tipo'        => 'boolean',
                'descripcion' => 'Aplicar descuentos automáticos por llegadas tarde',
            ],
            [
                'clave'       => 'descuentos_faltas',
                'valor'       => 'false',
                'grupo'       => 'asistencia',
                'tipo'        => 'boolean',
                'descripcion' => 'Aplicar descuentos automáticos por faltas no justificadas',
            ],
            [
                'clave'       => 'notificaciones_retrasos',
                'valor'       => 'true',
                'grupo'       => 'asistencia',
                'tipo'        => 'boolean',
                'descripcion' => 'Enviar alertas a RRHH cuando un empleado acumula retrasos',
            ],
            [
                // Valor reutilizado del .env actual: EMPRESA_LAT=-17.394291
                'clave'       => 'empresa_lat',
                'valor'       => '-17.394291',
                'grupo'       => 'asistencia',
                'tipo'        => 'decimal',
                'descripcion' => 'Latitud de la ubicación de la empresa (entre -90 y 90)',
            ],
            [
                // Valor reutilizado del .env actual: EMPRESA_LON=-66.074135
                'clave'       => 'empresa_lon',
                'valor'       => '-66.074135',
                'grupo'       => 'asistencia',
                'tipo'        => 'decimal',
                'descripcion' => 'Longitud de la ubicación de la empresa (entre -180 y 180)',
            ],
            [
                // Valor reutilizado del código hardcodeado: 500 metros
                'clave'       => 'radio_asistencia',
                'valor'       => '500',
                'grupo'       => 'asistencia',
                'tipo'        => 'integer',
                'descripcion' => 'Radio permitido en metros para registrar asistencia desde la empresa',
            ],
            [
                'clave'       => 'validacion_ubicacion',
                'valor'       => 'true',
                'grupo'       => 'asistencia',
                'tipo'        => 'boolean',
                'descripcion' => 'Activar validación de ubicación GPS al registrar asistencia',
            ],

            // ─── PLANILLAS ─────────────────────────────────────────────────────────
            [
                'clave'       => 'moneda',
                'valor'       => 'BOB',
                'grupo'       => 'planillas',
                'tipo'        => 'string',
                'descripcion' => 'Moneda utilizada en las planillas (BOB, USD)',
            ],
            [
                'clave'       => 'salario_minimo',
                'valor'       => '2500',
                'grupo'       => 'planillas',
                'tipo'        => 'decimal',
                'descripcion' => 'Salario mínimo nacional de referencia',
            ],
            [
                'clave'       => 'dia_corte',
                'valor'       => '25',
                'grupo'       => 'planillas',
                'tipo'        => 'integer',
                'descripcion' => 'Día del mes en que se cierra el período de planilla',
            ],
            [
                'clave'       => 'calculo_automatico_salarios',
                'valor'       => 'true',
                'grupo'       => 'planillas',
                'tipo'        => 'boolean',
                'descripcion' => 'Calcular montos automáticamente en base a días trabajados',
            ],
            [
                'clave'       => 'calculo_automatico_horas',
                'valor'       => 'true',
                'grupo'       => 'planillas',
                'tipo'        => 'boolean',
                'descripcion' => 'Obtener horas trabajadas directamente del control de asistencia',
            ],
            [
                'clave'       => 'aplicacion_automatica_adelantos',
                'valor'       => 'true',
                'grupo'       => 'planillas',
                'tipo'        => 'boolean',
                'descripcion' => 'Descontar automáticamente los adelantos aprobados en la planilla',
            ],
            [
                'clave'       => 'aplicacion_automatica_descuentos',
                'valor'       => 'true',
                'grupo'       => 'planillas',
                'tipo'        => 'boolean',
                'descripcion' => 'Aplicar automáticamente los descuentos por faltas en la planilla',
            ],
            [
                'clave'       => 'generacion_automatica_planillas',
                'valor'       => 'false',
                'grupo'       => 'planillas',
                'tipo'        => 'boolean',
                'descripcion' => 'Generar la nómina automáticamente el día de corte',
            ],

            // ─── SISTEMA ───────────────────────────────────────────────────────────
            [
                'clave'       => 'zona_horaria',
                'valor'       => 'America/La_Paz',
                'grupo'       => 'sistema',
                'tipo'        => 'string',
                'descripcion' => 'Zona horaria del servidor y los registros del sistema',
            ],
            [
                'clave'       => 'tema_visual',
                'valor'       => 'claro',
                'grupo'       => 'sistema',
                'tipo'        => 'string',
                'descripcion' => 'Tema visual de la interfaz (claro, oscuro)',
            ],
            [
                'clave'       => 'notificaciones_sistema',
                'valor'       => 'true',
                'grupo'       => 'sistema',
                'tipo'        => 'boolean',
                'descripcion' => 'Recibir mensajes y alertas en tiempo real',
            ],
            [
                'clave'       => 'auditoria',
                'valor'       => 'true',
                'grupo'       => 'sistema',
                'tipo'        => 'boolean',
                'descripcion' => 'Guardar un log de quién realiza cambios en el sistema',
            ],
        ];

        foreach ($configuraciones as $config) {
            DB::table('configuracion')->updateOrInsert(
                ['clave' => $config['clave']],
                array_merge($config, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
