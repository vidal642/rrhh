<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\RespuestaJson;
use App\Models\Ausencia;
use App\Models\Empleado;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

/**
 * Controlador para la gestión de ausencias del personal.
 */
class AusenciaControlador extends Controller
{
    use RespuestaJson;

    // Tipos de ausencia disponibles
    const TIPOS = ['Vacación', 'Permiso', 'Baja médica'];

    // Estados de las solicitudes
    const ESTADOS = ['Pendiente', 'Aprobado', 'Rechazado'];

    /**
     * Listar todas las ausencias con filtros.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $consulta = Ausencia::with(['empleado.departamento', 'empleado.cargo']);

            // Filtro por tipo
            if ($request->filled('tipo')) {
                $consulta->where('tipo', $request->tipo);
            }

            // Filtro por estado
            if ($request->filled('estado')) {
                $consulta->where('estado', $request->estado);
            }

            // Filtro por empleado
            if ($request->filled('id_empleado') || $request->filled('empleado_id')) {
                $consulta->where('id_empleado', $request->input('id_empleado') ?? $request->input('empleado_id'));
            }

            // Filtro por departamento
            if ($request->filled('id_departamento') || $request->filled('departamento_id')) {
                $consulta->whereHas('empleado', function ($q) use ($request) {
                    $q->where('id_departamento', $request->input('id_departamento') ?? $request->input('departamento_id'));
                });
            }

            // Filtro por rango de fechas
            if ($request->filled('fecha_inicio')) {
                $consulta->where('fecha_inicio', '>=', $request->fecha_inicio);
            }
            if ($request->filled('fecha_fin')) {
                $consulta->where('fecha_fin', '<=', $request->fecha_fin);
            }

            $ausencias = $consulta->orderBy('fecha_inicio', 'desc')->get();

            return $this->respuestaExito($ausencias, 'Ausencias obtenidas correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Registrar una nueva ausencia.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if ($user && $user->rol === 'Empleado') {
                $request->merge(['id_empleado' => $user->id_empleado]);
            }

            $validado = $request->validate([
                'id_empleado'  => 'required|exists:empleado,id_empleado',
                'tipo'         => ['required', Rule::in(self::TIPOS)],
                'fecha_inicio' => 'required|date|after_or_equal:today',
                'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
                'estado'       => ['nullable', Rule::in(self::ESTADOS)],
                'motivo'       => 'nullable|string|max:500',
            ], [
                'id_empleado.required' => 'Seleccione un empleado válido.',
                'id_empleado.exists'   => 'Seleccione un empleado válido.',
                'tipo.required'        => 'Seleccione un tipo de ausencia válido.',
                'tipo.in'              => 'Seleccione un tipo de ausencia válido.',
                'fecha_inicio.required'=> 'La fecha de inicio es obligatoria.',
                'fecha_inicio.date'            => 'La fecha de inicio ingresada no es correcta.',
                'fecha_inicio.after_or_equal'  => 'La fecha de inicio no puede ser en el pasado.',
                'fecha_fin.required'           => 'La fecha de fin es obligatoria.',
                'fecha_fin.date'               => 'La fecha de fin ingresada no es correcta.',
                'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser menor que la fecha de inicio.',
                'estado.in'            => 'Seleccione un estado válido.',
                'motivo.max'           => 'El motivo no debe exceder los 500 caracteres.',
            ]);

            $validado['estado'] = $validado['estado'] ?? 'Pendiente';

            $ausencia = Ausencia::create($validado);
            $ausencia->load(['empleado.departamento', 'empleado.cargo']);



            return $this->respuestaCreado($ausencia, 'Ausencia registrada correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Obtener una ausencia específica.
     */
    public function show($id): JsonResponse
    {
        try {
            $ausencia = Ausencia::with(['empleado.departamento', 'empleado.cargo'])->findOrFail($id);
            return $this->respuestaExito($ausencia, 'Ausencia obtuvo correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Actualizar una ausencia.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $ausencia = Ausencia::findOrFail($id);

            $user = $request->user();
            if ($user && $user->rol === 'Empleado') {
                $request->merge(['id_empleado' => $user->id_empleado]);
            }

            $validado = $request->validate([
                'id_empleado'  => 'sometimes|exists:empleado,id_empleado',
                'tipo'         => ['sometimes', Rule::in(self::TIPOS)],
                'fecha_inicio' => 'sometimes|date|after_or_equal:today',
                'fecha_fin'    => 'sometimes|date|after_or_equal:fecha_inicio',
                'estado'       => ['sometimes', Rule::in(self::ESTADOS)],
                'motivo'       => 'nullable|string|max:500',
            ], [
                'id_empleado.exists'   => 'Seleccione un empleado válido.',
                'tipo.in'              => 'Seleccione un tipo de ausencia válido.',
                'fecha_inicio.date'            => 'La fecha de inicio ingresada no es correcta.',
                'fecha_inicio.after_or_equal'  => 'La fecha de inicio no puede ser en el pasado.',
                'fecha_fin.date'               => 'La fecha de fin ingresada no es correcta.',
                'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser menor que la fecha de inicio.',
                'estado.in'            => 'Seleccione un estado válido.',
                'motivo.max'           => 'El motivo no debe exceder los 500 caracteres.',
            ]);

            $ausencia->update($validado);
            $ausencia->load(['empleado.departamento', 'empleado.cargo']);

            return $this->respuestaExito($ausencia, 'Ausencia actualizada correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Eliminar una ausencia.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $ausencia = Ausencia::findOrFail($id);
            $ausencia->delete();
            return $this->respuestaEliminado('Ausencia eliminada correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Aprobar o rechazar una solicitud de ausencia.
     */
    public function cambiarEstado(Request $request, $id): JsonResponse
    {
        try {
            $ausencia = Ausencia::findOrFail($id);

            $validado = $request->validate([
                'estado' => ['required', Rule::in(self::ESTADOS)],
            ], [
                'estado.required' => 'Seleccione un estado válido.',
                'estado.in'       => 'Seleccione un estado válido.',
            ]);

            $ausencia->update(['estado' => $validado['estado']]);
            $ausencia->load(['empleado.departamento', 'empleado.cargo']);

            $mensaje = match ($validado['estado']) {
                'Aprobado'  => 'Solicitud aprobada correctamente',
                'Rechazado' => 'Solicitud rechazada',
                default     => 'Estado actualizado',
            };

            return $this->respuestaExito($ausencia, $mensaje);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }
}
