<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('asistencia', function (Blueprint $table) {
            $table->index(['id_empleado', 'fecha'], 'idx_asistencia_emp_fecha');
        });

        Schema::table('planilla', function (Blueprint $table) {
            $table->index(['id_empleado', 'mes', 'anio'], 'idx_planilla_emp_mes_anio');
        });

        Schema::table('adelantos', function (Blueprint $table) {
            $table->index(['empleado_id', 'estado'], 'idx_adelantos_emp_estado');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('asistencia', function (Blueprint $table) {
            $table->dropIndex('idx_asistencia_emp_fecha');
        });

        Schema::table('planilla', function (Blueprint $table) {
            $table->dropIndex('idx_planilla_emp_mes_anio');
        });

        Schema::table('adelantos', function (Blueprint $table) {
            $table->dropIndex('idx_adelantos_emp_estado');
        });
    }
};
