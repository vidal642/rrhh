<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adelanto extends Model
{
    use HasFactory;

    protected $table = 'adelantos';
    protected $primaryKey = 'id';

    protected $fillable = [
        'empleado_id',
        'planilla_id',
        'monto',
        'fecha',
        'descripcion',
        'estado',
        'fecha_aprobacion',
        'fecha_rechazo',
        'aprobado_por',
        'motivo_rechazo',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id', 'id_empleado');
    }

    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'planilla_id', 'id_planilla');
    }
}
