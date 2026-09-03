<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. departamento
        Schema::create('departamento', function (Blueprint $table) {
            $table->id('id_departamento');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        // 2. cargo
        Schema::create('cargo', function (Blueprint $table) {
            $table->id('id_cargo');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('salario_referencia', 10, 2)->nullable();
            
            $table->unsignedBigInteger('id_departamento')->nullable();
            $table->foreign('id_departamento')->references('id_departamento')->on('departamento')->onDelete('set null');
            
            $table->timestamps();
        });

        // 3. empleado
        Schema::create('empleado', function (Blueprint $table) {
            $table->id('id_empleado');
            $table->string('nombre');
            $table->string('apellido');
            $table->string('ci')->unique();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('telefono')->nullable();
            $table->string('correo')->nullable();
            $table->text('direccion')->nullable();
            $table->date('fecha_contratacion');
            $table->string('estado')->default('Activo'); // Activo, Inactivo, etc.
            $table->decimal('salario_base', 10, 2);
            $table->string('foto_rostro')->nullable();
            
            $table->unsignedBigInteger('id_departamento')->nullable();
            $table->unsignedBigInteger('id_cargo')->nullable();

            $table->foreign('id_departamento')->references('id_departamento')->on('departamento')->onDelete('set null');
            $table->foreign('id_cargo')->references('id_cargo')->on('cargo')->onDelete('set null');
            
            $table->timestamps();
        });

        // 4. usuario
        Schema::create('usuario', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('usuario')->unique();
            $table->string('password');
            $table->string('rol')->default('Empleado'); // Administrador, Empleado, etc.
            
            $table->unsignedBigInteger('id_empleado')->nullable();
            $table->foreign('id_empleado')->references('id_empleado')->on('empleado')->onDelete('cascade');
            
            $table->rememberToken();
            $table->timestamps();
        });

        // 5. asistencia
        Schema::create('asistencia', function (Blueprint $table) {
            $table->id('id_asistencia');
            $table->unsignedBigInteger('id_empleado');
            $table->date('fecha');
            $table->time('hora_entrada')->nullable();
            $table->time('hora_salida')->nullable();
            $table->decimal('horas_trabajadas', 5, 2)->nullable();
            $table->string('estado'); // Presente, Retraso, Falta
            $table->string('metodo_registro')->nullable();

            $table->foreign('id_empleado')->references('id_empleado')->on('empleado')->onDelete('cascade');
            $table->timestamps();
        });

        // 6. ausencia
        Schema::create('ausencia', function (Blueprint $table) {
            $table->id('id_ausencia');
            $table->unsignedBigInteger('id_empleado');
            $table->string('tipo'); // Vacación, Permiso, Baja Médica
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->text('motivo')->nullable();
            $table->string('estado')->default('Pendiente'); // Pendiente, Aprobado, Rechazado

            $table->foreign('id_empleado')->references('id_empleado')->on('empleado')->onDelete('cascade');
            $table->timestamps();
        });

        // 7. planilla
        Schema::create('planilla', function (Blueprint $table) {
            $table->id('id_planilla');
            $table->unsignedBigInteger('id_empleado');
            $table->integer('mes');
            $table->integer('anio');
            $table->decimal('salario_base', 10, 2);
            $table->decimal('bonos', 10, 2)->default(0);
            $table->decimal('descuentos', 10, 2)->default(0);
            $table->decimal('horas_extra', 10, 2)->default(0);
            $table->decimal('salario_total', 10, 2);
            $table->date('fecha_pago')->nullable();

            $table->foreign('id_empleado')->references('id_empleado')->on('empleado')->onDelete('cascade');
            $table->timestamps();
        });

        // 8. beneficio_social
        Schema::create('beneficio_social', function (Blueprint $table) {
            $table->id('id_beneficio');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('monto', 10, 2)->nullable();
            $table->timestamps();
        });

        // 9. empleado_beneficio
        Schema::create('empleado_beneficio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_empleado');
            $table->unsignedBigInteger('id_beneficio');
            $table->date('fecha_asignacion');

            $table->foreign('id_empleado')->references('id_empleado')->on('empleado')->onDelete('cascade');
            $table->foreign('id_beneficio')->references('id_beneficio')->on('beneficio_social')->onDelete('cascade');
            $table->timestamps();
        });

        // 10. descuento
        Schema::create('descuento', function (Blueprint $table) {
            $table->id('id_descuento');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('monto', 10, 2)->nullable();
            $table->string('tipo')->nullable(); // Porcentaje, Monto fijo
            $table->timestamps();
        });

        // 11. empleado_descuento
        Schema::create('empleado_descuento', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_empleado');
            $table->unsignedBigInteger('id_descuento');
            $table->date('fecha');

            $table->foreign('id_empleado')->references('id_empleado')->on('empleado')->onDelete('cascade');
            $table->foreign('id_descuento')->references('id_descuento')->on('descuento')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_descuento');
        Schema::dropIfExists('descuento');
        Schema::dropIfExists('empleado_beneficio');
        Schema::dropIfExists('beneficio_social');
        Schema::dropIfExists('planilla');
        Schema::dropIfExists('ausencia');
        Schema::dropIfExists('asistencia');
        Schema::dropIfExists('usuario');
        Schema::dropIfExists('empleado');
        Schema::dropIfExists('cargo');
        Schema::dropIfExists('departamento');
    }
};
