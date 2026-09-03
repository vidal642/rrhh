<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Extender tabla planilla con columnas de asistencia.
 * Todos los campos son nullable para no romper datos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planilla', function (Blueprint $table) {
            // Días realmente trabajados (calculados desde asistencia)
            $table->decimal('dias_trabajados', 5, 2)->nullable()->after('anio');

            // Total de horas trabajadas en el período (suma de horas_trabajadas de asistencia)
            $table->decimal('horas_trabajadas_total', 8, 2)->nullable()->after('dias_trabajados');

            // Cantidad de horas extra (horas > dias_trabajados * 8)
            $table->decimal('horas_extra_cantidad', 6, 2)->nullable()->after('horas_trabajadas_total');
        });
    }

    public function down(): void
    {
        Schema::table('planilla', function (Blueprint $table) {
            $table->dropColumn(['dias_trabajados', 'horas_trabajadas_total', 'horas_extra_cantidad']);
        });
    }
};
