<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

trait RegistraLogs
{
    /**
     * Registra un evento específico de reconocimiento facial.
     *
     * @param int|null $id_empleado ID del empleado involucrado (puede ser null si no se identifica)
     * @param string $accion Tipo de evento (ej: RECONOCIMIENTO_EXITOSO)
     * @param string $detalle Descripción del resultado
     * @param string|null $fecha Fecha del evento, por defecto ahora
     * @return void
     */
    protected function registrarLogReconocimiento(?int $id_empleado, string $accion, string $detalle, ?string $fecha = null): void
    {
        try {
            DB::table('log_reconocimiento')->insert([
                'id_empleado' => $id_empleado,
                'accion'      => $accion,
                'detalle'     => $detalle,
                'fecha'       => $fecha ?? Carbon::now()
            ]);
        } catch (\Exception $e) {
            Log::error("Error al registrar log de reconocimiento [{$accion}]: " . $e->getMessage());
        }
    }


}
