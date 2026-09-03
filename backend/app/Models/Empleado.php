<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    use HasFactory;

    protected $table = 'empleado';
    protected $primaryKey = 'id_empleado';

    protected $fillable = [
        'nombre',
        'apellido',
        'ci',
        'extension_ci',
        'fecha_nacimiento',
        'telefono',
        'correo',
        'direccion',
        'fecha_contratacion',
        'estado',
        'salario_base',
        'foto_rostro',
        'id_departamento',
        'id_cargo',
        'codigo_empleado',
    ];

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'id_departamento', 'id_departamento');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'id_cargo', 'id_cargo');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'id_empleado', 'id_empleado');
    }

    public function ausencias()
    {
        return $this->hasMany(Ausencia::class, 'id_empleado', 'id_empleado');
    }

    public function planillas()
    {
        return $this->hasMany(Planilla::class, 'id_empleado', 'id_empleado');
    }

    public function usuario()
    {
        return $this->hasOne(Usuario::class, 'id_empleado', 'id_empleado');
    }

    public function beneficios()
    {
        return $this->belongsToMany(BeneficioSocial::class, 'empleado_beneficio', 'id_empleado', 'id_beneficio')
                    ->withPivot('fecha_asignacion')
                    ->withTimestamps();
    }

    public function descuentos()
    {
        return $this->belongsToMany(Descuento::class, 'empleado_descuento', 'id_empleado', 'id_descuento')
                    ->withPivot('fecha')
                    ->withTimestamps();
    }
}
