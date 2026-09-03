<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla reconocimiento_facial para registrar
 * cada intento de verificación biométrica.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reconocimiento_facial')) {
            return;
        }

        Schema::create('reconocimiento_facial', function (Blueprint $table) {
            $table->id('id_reconocimiento');

            // Empleado que intentó el reconocimiento
            $table->unsignedBigInteger('id_empleado');
            $table->foreign('id_empleado')
                  ->references('id_empleado')
                  ->on('empleado')
                  ->onDelete('cascade');

            // Asistencia generada (si el reconocimiento fue exitoso)
            $table->unsignedBigInteger('id_asistencia')->nullable();
            $table->foreign('id_asistencia')
                  ->references('id_asistencia')
                  ->on('asistencia')
                  ->onDelete('set null');

            // Resultado del reconocimiento
            $table->enum('resultado', ['reconocido', 'no_reconocido', 'error'])->default('error');
            $table->decimal('confianza', 5, 2)->default(0.00)->comment('Porcentaje de confianza 0-100');

            // Imagen capturada (ruta en storage)
            $table->string('imagen_capturada')->nullable();

            // Fecha y hora del intento
            $table->timestamp('fecha_hora')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconocimiento_facial');
    }
};
