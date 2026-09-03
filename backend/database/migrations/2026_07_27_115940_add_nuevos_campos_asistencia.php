<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asistencia', function (Blueprint $table) {
            $table->enum('estado_asistencia', ['PUNTUAL', 'TARDE', 'FALTA PARCIAL'])
                  ->nullable()
                  ->after('estado')
                  ->comment('Clasificación de puntualidad de la asistencia');
            
            $table->decimal('porcentaje_similitud', 5, 2)
                  ->nullable()
                  ->after('confianza_facial')
                  ->comment('Porcentaje de similitud facial (basado en Distancia Euclidiana)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asistencia', function (Blueprint $table) {
            $table->dropColumn(['estado_asistencia', 'porcentaje_similitud']);
        });
    }
};
