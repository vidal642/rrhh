<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\RespuestaJson;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Controlador para la gestión de departamentos.
 */
class DepartamentoControlador extends Controller
{
    use RespuestaJson;

    /**
     * Listar todos los departamentos con conteo de empleados.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $departamentos = Cache::remember('departamentos_all', 3600, function () {
                return Departamento::withCount('empleados')
                    ->orderBy('nombre')
                    ->get();
            });

            return $this->respuestaExito($departamentos, 'Departamentos obtenidos correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Crear un nuevo departamento.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validado = $request->validate([
                'nombre'      => 'required|string|min:2|max:100|unique:departamento,nombre',
                'descripcion' => 'nullable|string|max:255',
            ], [
                'nombre.required' => 'El nombre del departamento es obligatorio.',
                'nombre.min'      => 'El nombre debe tener al menos 2 caracteres.',
                'nombre.unique'   => 'El departamento ya se encuentra registrado.',
            ]);
            $departamento = Departamento::create($validado);
            Cache::forget('departamentos_all');
            return $this->respuestaCreado($departamento, 'Departamento creado correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Obtener un departamento específico.
     */
    public function show($id): JsonResponse
    {
        try {
            $departamento = Departamento::with('empleados')->findOrFail($id);
            return $this->respuestaExito($departamento, 'Departamento obtenido correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Actualizar un departamento.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $departamento = Departamento::findOrFail($id);
            
            $validado = $request->validate([
                'nombre'      => "required|string|min:2|max:100|unique:departamento,nombre,{$id},id_departamento",
                'descripcion' => 'nullable|string|max:255',
            ], [
                'nombre.required' => 'El nombre del departamento es obligatorio.',
                'nombre.min'      => 'El nombre debe tener al menos 2 caracteres.',
                'nombre.unique'   => 'El departamento ya se encuentra registrado.',
            ]);

            $departamento->update($validado);
            Cache::forget('departamentos_all');
            return $this->respuestaExito($departamento, 'Departamento actualizado correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Eliminar un departamento (solo si no tiene empleados activos).
     */
    public function destroy($id): JsonResponse
    {
        try {
            $departamento = Departamento::findOrFail($id);

            if ($departamento->empleados()->count() > 0) {
                return $this->respuestaError(
                    'No se puede eliminar: el departamento tiene empleados asignados.',
                    409
                );
            }

            $nombre = $departamento->nombre;
            $departamento->delete();
            Cache::forget('departamentos_all');
            return $this->respuestaEliminado("Departamento '{$nombre}' eliminado correctamente");
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Obtener todos los cargos que pertenecen al departamento.
     */
    public function cargos($id): JsonResponse
    {
        try {
            $departamento = Departamento::findOrFail($id);
            $cargos = $departamento->cargos()->orderBy('nombre')->get();
            return $this->respuestaExito($cargos, 'Cargos del departamento obtenidos correctamente');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->respuestaError('Departamento no encontrado', 404);
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }
}
