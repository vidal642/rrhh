<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeneficioSocial extends Model
{
    use HasFactory;

    protected $table = 'beneficio_social';
    protected $primaryKey = 'id_beneficio';

    protected $fillable = [
        'nombre',
        'descripcion',
        'monto',
    ];

    public function empleados()
    {
        return $this->belongsToMany(Empleado::class, 'empleado_beneficio', 'id_beneficio', 'id_empleado')
                    ->withPivot('fecha_asignacion')
                    ->withTimestamps();
    }
}
