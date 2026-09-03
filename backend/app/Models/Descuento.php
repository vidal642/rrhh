<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Descuento extends Model
{
    use HasFactory;

    protected $table = 'descuento';
    protected $primaryKey = 'id_descuento';

    protected $fillable = [
        'nombre',
        'descripcion',
        'monto',
        'tipo',
    ];

    public function empleados()
    {
        return $this->belongsToMany(Empleado::class, 'empleado_descuento', 'id_descuento', 'id_empleado')
                    ->withPivot('fecha')
                    ->withTimestamps();
    }
}
