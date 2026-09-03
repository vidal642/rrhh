<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla empleado_rostro.
 * Almacena los embeddings faciales generados por el microservicio Python.
 */
class EmpleadoRostro extends Model
{
    use HasFactory;

    protected $table      = 'empleado_rostro';
    protected $primaryKey = 'id_rostro';

    // Los timestamps de Laravel (created_at/updated_at) no aplican;
    // usamos fecha_registro y ultima_actualizacion definidos en la BD.
    public $timestamps = false;

    protected $fillable = [
        'id_empleado',
        'embedding',
        'imagen_referencia',
        'modelo_usado',
        'estado',
        'observaciones',
        'fecha_registro',
        'ultima_actualizacion',
    ];

    /**
     * Castings automáticos.
     * El embedding se deserializa automáticamente como array de PHP.
     */
    protected function casts(): array
    {
        return [
            'embedding'            => 'array',
            'fecha_registro'       => 'datetime',
            'ultima_actualizacion' => 'datetime',
        ];
    }

    // ─── Relaciones ──────────────────────────────────────────────────────────

    /**
     * Empleado al que pertenece este rostro.
     */
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    /**
     * Solo rostros activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }
}
