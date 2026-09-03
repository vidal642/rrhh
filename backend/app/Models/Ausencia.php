<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ausencia extends Model
{
    use HasFactory;

    protected $table = 'ausencia';
    protected $primaryKey = 'id_ausencia';

    protected $fillable = [
        'id_empleado',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'estado',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }
}
