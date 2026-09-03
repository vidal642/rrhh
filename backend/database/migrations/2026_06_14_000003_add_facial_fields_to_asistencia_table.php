<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega los campos de reconocimiento facial
 * a la tabla asistencia existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencia', function (Blueprint $table) {
            if (!Schema::hasColumn('asistencia', 'confianza_facial')) {
                $table->decimal('confianza_facial', 5, 2)
                      ->nullable()
                      ->after('metodo_registro')
                      ->comment('Confianza del reconocimiento facial en %');
            }

            if (!Schema::hasColumn('asistencia', 'imagen_verificacion')) {
                $table->string('imagen_verificacion')
                      ->nullable()
                      ->after('confianza_facial')
                      ->comment('Ruta de la imagen usada para verificar la asistencia');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asistencia', function (Blueprint $table) {
            $table->dropColumn(['confianza_facial', 'imagen_verificacion']);
        });
    }
};
