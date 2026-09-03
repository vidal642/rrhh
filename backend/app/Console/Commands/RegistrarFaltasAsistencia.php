<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RegistrarFaltasAsistencia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asistencia:registrar-faltas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Registra automáticamente faltas para empleados que no marcaron asistencia hoy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fechaHoy = Carbon::now()->toDateString();
        
        $this->info("Iniciando registro de faltas para la fecha: {$fechaHoy}");

        // Obtener IDs de empleados activos
        $empleadosActivos = DB::table('empleado')->where('estado', 'Activo')->pluck('id_empleado');
        
        $faltasRegistradas = 0;

        foreach ($empleadosActivos as $idEmpleado) {
            // Verificar si tiene asistencia hoy
            $asistencia = DB::table('asistencia')
                ->where('id_empleado', $idEmpleado)
                ->where('fecha', $fechaHoy)
                ->first();

            if (!$asistencia) {
                // Registrar falta
                DB::table('asistencia')->insert([
                    'id_empleado' => $idEmpleado,
                    'fecha' => $fechaHoy,
                    'estado' => 'Falta',
                    'estado_asistencia' => 'falta',
                    'metodo_registro' => 'Administrador', // or 'Sistema' if exists in METODOS, fallback to Administrador
                    'registro_automatico' => 1,
                    'observacion' => 'Falta generada automáticamente por el sistema',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
                
                $faltasRegistradas++;
            }
        }

        $this->info("Proceso completado. Faltas registradas: {$faltasRegistradas}");
    }
}
