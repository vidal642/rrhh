<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DescuentoManual extends Model
{
    use HasFactory;

    protected $table = 'descuentos_manuales';
    protected $primaryKey = 'id_descuento_manual';

    protected $fillable = [
        'id_planilla',
        'monto',
        'fecha',
        'descripcion',
    ];

    public function planilla()
    {
        return $this->belongsTo(Planilla::class, 'id_planilla', 'id_planilla');
    }
}
