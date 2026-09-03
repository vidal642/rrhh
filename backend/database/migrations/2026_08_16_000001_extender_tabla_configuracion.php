<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extiende la tabla configuracion existente con columnas de grupo, tipo y descripcion.
     * Se ejecuta SOLO si la columna "grupo" aún no existe para ser idempotente.
     */
    public function up(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            if (!Schema::hasColumn('configuracion', 'grupo')) {
                $table->string('grupo')->nullable()->after('clave')->index();
            }
            if (!Schema::hasColumn('configuracion', 'tipo')) {
                $table->string('tipo')->nullable()->after('grupo');
                // Valores posibles: boolean | integer | decimal | string | time | json
            }
            if (!Schema::hasColumn('configuracion', 'descripcion')) {
                $table->text('descripcion')->nullable()->after('tipo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            $table->dropIndex(['grupo']);
            $table->dropColumn(['grupo', 'tipo', 'descripcion']);
        });
    }
};
