<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\RespuestaJson;
use App\Models\Cargo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Controlador para la gestión de cargos.
 */
class CargoControlador extends Controller
{
    use RespuestaJson;

    /**
     * Listar todos los cargos con conteo de empleados.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $cargos = Cache::remember('cargos_all', 3600, function () {
                return Cargo::with('departamento')->withCount('empleados')
                    ->orderBy('nombre')
                    ->get();
            });

            return $this->respuestaExito($cargos, 'Cargos obtenidos correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validado = $request->validate([
                'nombre'             => 'required|string|min:2|max:100|unique:cargo,nombre',
                'descripcion'        => 'nullable|string|max:255',
                'salario_referencia' => 'nullable|numeric|min:3300',
                'id_departamento'    => 'required|exists:departamento,id_departamento',
            ], [
                'nombre.required' => 'El nombre del cargo es obligatorio.',
                'nombre.min'      => 'El nombre debe tener al menos 2 caracteres.',
                'nombre.unique'   => 'El cargo ya se encuentra registrado.',
                'salario_referencia.numeric' => 'El salario de referencia debe ser un número.',
                'salario_referencia.min' => 'El salario de referencia no puede ser menor al mínimo nacional (3300 Bs).',
                'id_departamento.required' => 'Debe seleccionar a qué departamento pertenece el cargo.',
                'id_departamento.exists' => 'El departamento seleccionado no es válido.',
            ]);
            $cargo = Cargo::create($validado);
            Cache::forget('cargos_all');
            return $this->respuestaCreado($cargo, 'Cargo creado correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $cargo = Cargo::with('empleados', 'departamento')->findOrFail($id);
            return $this->respuestaExito($cargo, 'Cargo obtenido correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $cargo = Cargo::findOrFail($id);
            
            $validado = $request->validate([
                'nombre'             => "required|string|min:2|max:100|unique:cargo,nombre,{$id},id_cargo",
                'descripcion'        => 'nullable|string|max:255',
                'salario_referencia' => 'nullable|numeric|min:3300',
                'id_departamento'    => 'required|exists:departamento,id_departamento',
            ], [
                'nombre.required' => 'El nombre del cargo es obligatorio.',
                'nombre.min'      => 'El nombre debe tener al menos 2 caracteres.',
                'nombre.unique'   => 'El cargo ya se encuentra registrado.',
                'salario_referencia.numeric' => 'El salario de referencia debe ser un número.',
                'salario_referencia.min' => 'El salario de referencia no puede ser menor al mínimo nacional (3300 Bs).',
                'id_departamento.required' => 'Debe seleccionar a qué departamento pertenece el cargo.',
                'id_departamento.exists' => 'El departamento seleccionado no es válido.',
            ]);

            $cargo->update($validado);
            Cache::forget('cargos_all');
            return $this->respuestaExito($cargo, 'Cargo actualizado correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $cargo = Cargo::findOrFail($id);
            if ($cargo->empleados()->count() > 0) {
                return $this->respuestaError(
                    'No se puede eliminar: el cargo tiene empleados asignados.',
                    409
                );
            }

            $nombre = $cargo->nombre;
            $cargo->delete();
            Cache::forget('cargos_all');
            return $this->respuestaEliminado("Cargo '{$nombre}' eliminado correctamente");
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }
}
