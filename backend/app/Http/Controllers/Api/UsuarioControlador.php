<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\RespuestaJson;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Controlador para la gestión de usuarios del sistema.
 */
class UsuarioControlador extends Controller
{
    use RespuestaJson;

    /**
     * Listar todos los usuarios del sistema.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $consulta = Usuario::with('empleado');

            if ($request->filled('buscar')) {
                $termino = $request->buscar;
                $consulta->where(function ($q) use ($termino) {
                    $q->where('usuario', 'like', "%{$termino}%")
                      ->orWhere('rol', 'like', "%{$termino}%");
                });
            }

            $usuarios = $consulta->orderBy('usuario')
                ->get();

            return $this->respuestaExito($usuarios, 'Usuarios obtenidos correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Crear un nuevo usuario del sistema.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validado = $request->validate([
                'usuario'     => 'required|string|min:4|unique:usuario,usuario|max:100',
                'password'    => 'required|string|min:6|confirmed',
                'rol'         => 'required|string',
                'id_empleado' => 'nullable|exists:empleado,id_empleado',
            ], [
                'usuario.required'   => 'El nombre de usuario es obligatorio.',
                'usuario.min'        => 'El nombre de usuario debe tener al menos 4 caracteres.',
                'usuario.unique'     => 'El nombre de usuario ya se encuentra registrado.',
                'password.required'  => 'La contraseña es obligatoria.',
                'password.min'       => 'La contraseña debe tener al menos 6 caracteres.',
                'password.confirmed' => 'Las contraseñas no coinciden.',
                'rol.required'       => 'Seleccione un rol válido.',
                'id_empleado.exists' => 'Seleccione un empleado válido.',
            ]);

            $validado['password'] = Hash::make($validado['password']);

            $usuario = Usuario::create($validado);
            $usuario->load('empleado');

            return $this->respuestaCreado($usuario, 'Usuario creado correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Obtener un usuario específico.
     */
    public function show($id): JsonResponse
    {
        try {
            $usuario = Usuario::with('empleado')->findOrFail($id);
            return $this->respuestaExito($usuario, 'Usuario obtenido correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Actualizar un usuario.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $usuario = Usuario::findOrFail($id);

            $validado = $request->validate([
                'usuario'     => ['sometimes', 'string', 'min:4', 'max:100', Rule::unique('usuario', 'usuario')->ignore($id, 'id_usuario')],
                'password'    => 'nullable|string|min:6|confirmed',
                'rol'         => 'sometimes|string',
                'id_empleado' => 'nullable|exists:empleado,id_empleado',
            ], [
                'usuario.min'        => 'El nombre de usuario debe tener al menos 4 caracteres.',
                'usuario.unique'     => 'El nombre de usuario ya se encuentra registrado.',
                'password.min'       => 'La contraseña debe tener al menos 6 caracteres.',
                'password.confirmed' => 'Las contraseñas no coinciden.',
                'id_empleado.exists' => 'Seleccione un empleado válido.',
            ]);

            if (!empty($validado['password'])) {
                $validado['password'] = Hash::make($validado['password']);
            } else {
                unset($validado['password']);
            }

            $usuario->update($validado);
            $usuario->load('empleado');

            return $this->respuestaExito($usuario, 'Usuario actualizado correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Eliminar un usuario del sistema.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $usuario = Usuario::findOrFail($id);

            // No permitir auto-eliminación
            if ($usuario->id_usuario === auth()->id()) {
                return $this->respuestaError('No puede eliminar su propio usuario.', 409);
            }

            $usuario->delete();
            return $this->respuestaEliminado('Usuario eliminado correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }

    /**
     * Actualizar el estado de un usuario.
     */
    public function cambiarEstado(Request $request, $id): JsonResponse
    {
        try {
            $usuario = Usuario::findOrFail($id);
            
            // No permitir auto-desactivación
            if ($usuario->id_usuario === auth()->id()) {
                return $this->respuestaError('No puede cambiar el estado de su propio usuario.', 409);
            }

            $validado = $request->validate([
                'estado' => 'required|in:Activo,Inactivo',
            ], [
                'estado.required' => 'El estado es obligatorio.',
                'estado.in'       => 'El estado no es válido.',
            ]);

            $usuario->update(['estado' => $validado['estado']]);
            $usuario->load('empleado');

            return $this->respuestaExito($usuario, 'Estado actualizado correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->respuestaServidor();
        }
    }
}
