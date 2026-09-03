<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\RespuestaJson;
use App\Models\Adelanto;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdelantoControlador extends Controller
{
    use RespuestaJson;

    public function index(Request $request): JsonResponse
    {
        try {
            $consulta = Adelanto::with(['empleado.departamento', 'empleado.cargo', 'planilla']);

            if ($request->filled('empleado_id') || $request->filled('id_empleado')) {
                $consulta->where('empleado_id', $request->input('empleado_id') ?? $request->input('id_empleado'));
            }

            if ($request->filled('estado')) {
                $consulta->where('estado', $request->estado);
            }

            $adelantos = $consulta->orderBy('fecha', 'desc')->get();

            return $this->respuestaExito($adelantos, 'Adelantos obtenidos correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if ($user && $user->rol === 'Empleado') {
                $request->merge(['empleado_id' => $user->id_empleado]);
            }

            $validado = $request->validate([
                'empleado_id' => 'required|exists:empleado,id_empleado',
                'monto'       => 'required|numeric|min:0.01',
                'fecha'       => 'required|date|after_or_equal:today',
                'descripcion' => 'required|string|min:2',
            ], [
                'empleado_id.required' => 'Seleccione un empleado válido.',
                'empleado_id.exists'   => 'Seleccione un empleado válido.',
                'monto.required'       => 'El monto es obligatorio.',
                'monto.numeric'        => 'El monto debe ser numérico.',
                'monto.min'            => 'El monto no puede ser negativo o cero.',
                'fecha.required'       => 'La fecha es obligatoria.',
                'fecha.date'           => 'La fecha no es correcta.',
                'fecha.after_or_equal' => 'La fecha no puede ser en el pasado.',
                'descripcion.required' => 'La descripción es obligatoria.',
                'descripcion.min'      => 'La descripción debe tener al menos 2 caracteres.',
            ]);

            $validado['estado'] = 'Pendiente';

            $adelanto = Adelanto::create($validado);
            $adelanto->load('empleado');


            return $this->respuestaCreado($adelanto, 'Adelanto creado correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $adelanto = Adelanto::with(['empleado', 'planilla'])->findOrFail($id);
            return $this->respuestaExito($adelanto, 'Adelanto obtenido correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    public function aprobar($id): JsonResponse
    {
        try {
            $adelanto = Adelanto::findOrFail($id);
            if (strtoupper($adelanto->estado) !== 'PENDIENTE') {
                return $this->respuestaError('Este adelanto ya fue procesado y no puede cambiar de estado.', 409);
            }

            $adelanto->update([
                'estado' => 'APROBADO',
                'fecha_aprobacion' => Carbon::now(),
                'aprobado_por' => auth()->id()
            ]);
            $adelanto->load('empleado');
            
            return $this->respuestaExito($adelanto, 'El adelanto ha sido aprobado correctamente.');
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    public function rechazar(Request $request, $id): JsonResponse
    {
        try {
            $adelanto = Adelanto::findOrFail($id);
            if (strtoupper($adelanto->estado) !== 'PENDIENTE') {
                return $this->respuestaError('Este adelanto ya fue procesado y no puede cambiar de estado.', 409);
            }

            $adelanto->update([
                'estado' => 'RECHAZADO',
                'fecha_rechazo' => Carbon::now(),
                'motivo_rechazo' => $request->input('motivo_rechazo')
            ]);
            $adelanto->load('empleado');
            
            return $this->respuestaExito($adelanto, 'El adelanto ha sido rechazado correctamente.');
        } catch (\Exception $e) {
            return response()->json(['mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
