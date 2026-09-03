<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait RespuestaJson
 * Estandariza todas las respuestas JSON de la API del sistema RRHH.
 */
trait RespuestaJson
{
    /**
     * Respuesta exitosa con datos.
     */
    protected function respuestaExito(mixed $datos, string $mensaje = 'Operación exitosa', int $codigo = 200): JsonResponse
    {
        return response()->json([
            'exito'   => true,
            'mensaje' => $mensaje,
            'datos'   => $datos,
        ], $codigo);
    }

    /**
     * Respuesta de creación exitosa (201).
     */
    protected function respuestaCreado(mixed $datos, string $mensaje = 'Registro creado exitosamente'): JsonResponse
    {
        return $this->respuestaExito($datos, $mensaje, 201);
    }

    /**
     * Respuesta de error del cliente (4xx).
     */
    protected function respuestaError(string $mensaje = 'Error en la solicitud', int $codigo = 400, mixed $errores = null): JsonResponse
    {
        return response()->json([
            'exito'   => false,
            'mensaje' => $mensaje,
            'errores' => $errores,
        ], $codigo);
    }

    /**
     * Respuesta de recurso no encontrado (404).
     */
    protected function respuestaNoEncontrado(string $mensaje = 'Registro no encontrado'): JsonResponse
    {
        return $this->respuestaError($mensaje, 404);
    }

    /**
     * Respuesta de eliminación exitosa (204 sin cuerpo).
     */
    protected function respuestaEliminado(string $mensaje = 'Registro eliminado correctamente'): JsonResponse
    {
        return response()->json([
            'exito'   => true,
            'mensaje' => $mensaje,
        ], 200);
    }

    /**
     * Respuesta de error del servidor (500).
     */
    protected function respuestaServidor(string $mensaje = 'No se pudo procesar la información. Intente nuevamente.'): JsonResponse
    {
        // En caso de que se pase un error técnico, sobrescribirlo si es muy largo o contiene SQL
        if (str_contains($mensaje, 'SQLSTATE') || str_contains($mensaje, 'Exception')) {
            $mensaje = 'No se pudo guardar la información. Intente nuevamente.';
        }
        return $this->respuestaError($mensaje, 500);
    }
}
