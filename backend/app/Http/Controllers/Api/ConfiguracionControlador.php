<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\RespuestaJson;
use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ConfiguracionControlador extends Controller
{
    use RespuestaJson;

    /**
     * Obtener todas las configuraciones agrupadas y en formato plano clave-valor.
     */
    public function index(): JsonResponse
    {
        try {
            $configuraciones = Configuracion::all();

            $plano = [];
            foreach ($configuraciones as $config) {
                $valor = $config->valor;
                if ($valor === 'true')  $valor = true;
                if ($valor === 'false') $valor = false;
                $plano[$config->clave] = $valor;
            }

            $agrupado = $configuraciones->groupBy('grupo')
                ->map(fn($grupo) => $grupo->mapWithKeys(function ($c) {
                    $v = $c->valor;
                    if ($v === 'true')  $v = true;
                    if ($v === 'false') $v = false;
                    return [$c->clave => $v];
                }));

            return $this->respuestaExito(
                array_merge(['datos_raw' => $plano, 'por_grupo' => $agrupado], $plano),
                'Configuración obtenida correctamente.'
            );
        } catch (\Exception $e) {
            return $this->respuestaServidor('Error al obtener la configuración.');
        }
    }

    /**
     * Actualizar múltiples configuraciones a la vez.
     * NO incluye campos de empresa (eliminados del módulo).
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validado = $request->validate([
                'hora_entrada'           => 'required|date_format:H:i',
                'hora_salida'            => 'required|date_format:H:i|after:hora_entrada',
                'tolerancia_minutos'     => 'required|integer|min:0',
                'dias_laborales'         => 'required|string|max:50',
                'reconocimiento_facial'  => 'required|boolean',
                'descuentos_retrasos'    => 'required|boolean',
                'descuentos_faltas'      => 'required|boolean',
                'empresa_lat'            => 'nullable|numeric|between:-90,90',
                'empresa_lon'            => 'nullable|numeric|between:-180,180',
                'radio_asistencia'       => 'required|integer|min:1',
                'validacion_ubicacion'   => 'required|boolean',
                'moneda'                          => 'required|string|max:10',
                'salario_minimo'                  => 'required|numeric|min:0',
                'dia_corte'                       => 'required|integer|min:1|max:31',
                'calculo_automatico_salarios'     => 'required|boolean',
                'calculo_automatico_horas'        => 'required|boolean',
                'aplicacion_automatica_adelantos' => 'required|boolean',
                'aplicacion_automatica_descuentos'=> 'required|boolean',
                'generacion_automatica_planillas' => 'required|boolean',
                'zona_horaria'           => 'required|string|max:100',
                'tema_visual'            => 'required|in:claro,oscuro',
                'auditoria'              => 'required|boolean',
            ], [
                'hora_entrada.required'            => 'La hora de entrada es obligatoria.',
                'hora_entrada.date_format'         => 'El formato de la hora de entrada no es válido (HH:MM).',
                'hora_salida.required'             => 'La hora de salida es obligatoria.',
                'hora_salida.date_format'          => 'El formato de la hora de salida no es válido (HH:MM).',
                'hora_salida.after'                => 'La hora de salida debe ser posterior a la hora de entrada.',
                'tolerancia_minutos.required'      => 'Los minutos de tolerancia son obligatorios.',
                'tolerancia_minutos.min'           => 'La tolerancia no puede ser negativa.',
                'dias_laborales.required'          => 'Los días laborales son obligatorios.',
                'empresa_lat.between'              => 'La latitud debe estar entre -90 y 90.',
                'empresa_lat.numeric'              => 'La latitud debe ser un número decimal.',
                'empresa_lon.between'              => 'La longitud debe estar entre -180 y 180.',
                'empresa_lon.numeric'              => 'La longitud debe ser un número decimal.',
                'radio_asistencia.required'        => 'El radio de asistencia es obligatorio.',
                'radio_asistencia.min'             => 'El radio debe ser mayor a 0 metros.',
                'radio_asistencia.integer'         => 'El radio debe ser un número entero.',
                'moneda.required'                  => 'La moneda es obligatoria.',
                'salario_minimo.required'          => 'El salario mínimo es obligatorio.',
                'salario_minimo.min'               => 'El salario mínimo no puede ser negativo.',
                'dia_corte.required'               => 'El día de corte es obligatorio.',
                'dia_corte.min'                    => 'El día de corte mínimo es 1.',
                'dia_corte.max'                    => 'El día de corte máximo es 31.',
                'zona_horaria.required'            => 'La zona horaria es obligatoria.',
                'tema_visual.required'             => 'El tema visual es obligatorio.',
                'tema_visual.in'                   => 'El tema debe ser "claro" u "oscuro".',
            ]);

            $metadatos = [
                'hora_entrada'                    => ['asistencia', 'time'],
                'hora_salida'                     => ['asistencia', 'time'],
                'tolerancia_minutos'              => ['asistencia', 'integer'],
                'dias_laborales'                  => ['asistencia', 'string'],
                'reconocimiento_facial'           => ['asistencia', 'boolean'],
                'descuentos_retrasos'             => ['asistencia', 'boolean'],
                'descuentos_faltas'               => ['asistencia', 'boolean'],
                'empresa_lat'                     => ['asistencia', 'decimal'],
                'empresa_lon'                     => ['asistencia', 'decimal'],
                'radio_asistencia'                => ['asistencia', 'integer'],
                'validacion_ubicacion'            => ['asistencia', 'boolean'],
                'moneda'                          => ['planillas', 'string'],
                'salario_minimo'                  => ['planillas', 'decimal'],
                'dia_corte'                       => ['planillas', 'integer'],
                'calculo_automatico_salarios'     => ['planillas', 'boolean'],
                'calculo_automatico_horas'        => ['planillas', 'boolean'],
                'aplicacion_automatica_adelantos' => ['planillas', 'boolean'],
                'aplicacion_automatica_descuentos'=> ['planillas', 'boolean'],
                'generacion_automatica_planillas' => ['planillas', 'boolean'],
                'zona_horaria'                    => ['sistema', 'string'],
                'tema_visual'                     => ['sistema', 'string'],
                'auditoria'                       => ['sistema', 'boolean'],
            ];

            foreach ($validado as $clave => $valor) {
                if (is_bool($valor)) {
                    $valor = $valor ? 'true' : 'false';
                } else {
                    $valor = (string) $valor;
                }

                [$grupo, $tipo] = $metadatos[$clave] ?? [null, 'string'];

                Configuracion::updateOrCreate(
                    ['clave' => $clave],
                    ['valor' => $valor, 'grupo' => $grupo, 'tipo' => $tipo]
                );
            }

            return $this->respuestaExito(null, 'Configuración actualizada correctamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos.', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->respuestaServidor('Error al guardar la configuración.');
        }
    }
}
