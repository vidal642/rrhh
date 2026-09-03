<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planilla extends Model
{
    use HasFactory;

    protected $table = 'planilla';
    protected $primaryKey = 'id_planilla';

    protected $fillable = [
        'id_empleado',
        'mes',
        'anio',
        'salario_base',
        'bonos',
        'descuentos',
        'horas_extra',
        'salario_total',
        'fecha_pago',
        // Campos extendidos para automatización desde asistencia (nullable)
        'dias_trabajados',
        'horas_trabajadas_total',
        'horas_extra_cantidad',
        // Nuevos campos
        'descuentos_automaticos',
        'adelantos_aplicados',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleado');
    }

    public function descuentosManuales()
    {
        return $this->hasMany(DescuentoManual::class, 'id_planilla', 'id_planilla');
    }

    public function adelantos()
    {
        return $this->hasMany(Adelanto::class, 'planilla_id', 'id_planilla');
    }
}
