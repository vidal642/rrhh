<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\RespuestaJson;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Controlador de autenticación del sistema.
 * Maneja inicio y cierre de sesión con Sanctum.
 */
class AutenticacionControlador extends Controller
{
    use RespuestaJson;

    /**
     * Iniciar sesión y obtener token de acceso.
     */
    public function iniciarSesion(Request $request): JsonResponse
    {
        try {
            $credenciales = $request->validate([
                'usuario'  => 'required|string',
                'password' => 'required|string|min:6',
            ], [
                'usuario.required'  => 'El nombre de usuario es obligatorio.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.min'      => 'La contraseña debe tener al menos 6 caracteres.',
            ]);

            if (!Auth::attempt($credenciales)) {
                return $this->respuestaError('Credenciales inválidas. Verifique su usuario y contraseña.', 401);
            }

            $usuario = Auth::user();
            $token   = $usuario->createToken('rrhh-token')->plainTextToken;

            return $this->respuestaExito([
                'usuario' => [
                    'id'          => $usuario->id_usuario,
                    'usuario'     => $usuario->usuario,
                    'rol'         => $usuario->rol,
                    'id_empleado' => $usuario->id_empleado,
                ],
                'token' => $token,
            ], 'Sesión iniciada correctamente');
        } catch (ValidationException $e) {
            return $this->respuestaError('Datos inválidos', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->respuestaServidor('Error al iniciar sesión');
        }
    }

    /**
     * Cerrar sesión y revocar el token actual.
     */
    public function cerrarSesion(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->respuestaExito(null, 'Sesión cerrada correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor('Error al cerrar la sesión');
        }
    }

    /**
     * Obtener el perfil del usuario autenticado.
     */
    public function perfil(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            return $this->respuestaExito([
                'id'          => $usuario->id_usuario,
                'usuario'     => $usuario->usuario,
                'rol'         => $usuario->rol,
                'id_empleado' => $usuario->id_empleado,
                'creado_en'   => $usuario->created_at?->format('d/m/Y'),
            ], 'Perfil obtenido correctamente');
        } catch (\Exception $e) {
            return $this->respuestaServidor('Error al obtener el perfil');
        }
    }
}
