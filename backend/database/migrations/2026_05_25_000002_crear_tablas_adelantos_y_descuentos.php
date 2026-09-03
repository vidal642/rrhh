<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Añadir campos a la tabla planilla solo si no existen
        Schema::table('planilla', function (Blueprint $table) {
            if (!Schema::hasColumn('planilla', 'descuentos_automaticos')) {
                $table->decimal('descuentos_automaticos', 10, 2)->default(0)->after('descuentos');
            }
            if (!Schema::hasColumn('planilla', 'adelantos_aplicados')) {
                $table->decimal('adelantos_aplicados', 10, 2)->default(0)->after('descuentos_automaticos');
            }
        });

        // 2. Tabla descuentos_manuales (solo si no existe)
        if (!Schema::hasTable('descuentos_manuales')) {
            Schema::create('descuentos_manuales', function (Blueprint $table) {
                $table->id('id_descuento_manual');
                $table->unsignedBigInteger('id_planilla');
                $table->decimal('monto', 10, 2);
                $table->date('fecha');
                $table->text('descripcion'); // Obligatorio según requerimiento
                
                $table->foreign('id_planilla')->references('id_planilla')->on('planilla')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // 3. Tabla adelantos (solo si no existe)
        if (!Schema::hasTable('adelantos')) {
            Schema::create('adelantos', function (Blueprint $table) {
                $table->id(); // PK 'id'
                $table->unsignedBigInteger('empleado_id');
                $table->unsignedBigInteger('planilla_id')->nullable();
                $table->decimal('monto', 10, 2);
                $table->date('fecha');
                $table->text('descripcion')->nullable();
                $table->string('estado')->default('Pendiente'); // Pendiente, Aprobado, Rechazado
                
                $table->foreign('empleado_id')->references('id_empleado')->on('empleado')->onDelete('cascade');
                $table->foreign('planilla_id')->references('id_planilla')->on('planilla')->onDelete('set null');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('adelantos');
        Schema::dropIfExists('descuentos_manuales');
        
        Schema::table('planilla', function (Blueprint $table) {
            if (Schema::hasColumn('planilla', 'descuentos_automaticos')) {
                $table->dropColumn('descuentos_automaticos');
            }
            if (Schema::hasColumn('planilla', 'adelantos_aplicados')) {
                $table->dropColumn('adelantos_aplicados');
            }
        });
    }
};
