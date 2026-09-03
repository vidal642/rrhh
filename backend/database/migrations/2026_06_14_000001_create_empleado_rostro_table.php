<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla empleado_rostro para almacenar
 * los embeddings faciales de cada empleado.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('empleado_rostro')) {
            return; // Ya existe, probablemente creada en una migración anterior
        }

        Schema::create('empleado_rostro', function (Blueprint $table) {
            $table->id('id_rostro');

            // Relación con el empleado
            $table->unsignedBigInteger('id_empleado');
            $table->foreign('id_empleado')
                  ->references('id_empleado')
                  ->on('empleado')
                  ->onDelete('cascade');

            // Vector facial almacenado como JSON
            $table->longText('embedding')->comment('Vector facial en formato JSON (array de floats)');

            // Imagen de referencia (ruta relativa en storage)
            $table->string('imagen_referencia')->nullable()->comment('Ruta en storage/app/public/rostros/');

            // Modelo usado para generar el embedding
            $table->string('modelo_usado', 50)->default('Facenet512');

            // Control de estado
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->text('observaciones')->nullable();

            // Timestamps personalizados
            $table->timestamp('fecha_registro')->useCurrent();
            $table->timestamp('ultima_actualizacion')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_rostro');
    }
};
