<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla reconocimiento_facial.
 * Registra cada intento de verificación biométrica.
 */
class ReconocimientoFacial extends Model
{
    use HasFactory;

    protected $table      = 'reconocimiento_facial';
    protected $primaryKey = 'id_reconocimiento';

    // Usamos fecha_hora como timestamp único; no usamos created_at/updated_at.
    public $timestamps = false;

    protected $fillable = [
        'id_empleado',
        'id_asistencia',
        'resultado',
        'confianza',
        'imagen_capturada',
        'fecha_hora',
    ];

    protected function casts(): array
    {
        return [
            'confianza'  => 'float',
            'fecha_hora' => 'datetime',
        ];
    }

    // ─── Relaciones ──────────────────────────────────────────────────────────

    /**
     * Empleado que realizó el intento.
     */
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }

    /**
     * Asistencia generada (si el reconocimiento fue exitoso).
     */
    public function asistencia()
    {
        return $this->belongsTo(Asistencia::class, 'id_asistencia', 'id_asistencia');
    }
}
