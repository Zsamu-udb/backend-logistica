<?php
declare(strict_types=1);

namespace App\rutas\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramacionViaje extends Model
{
    protected $table = 'programaciones_viajes';

    protected $fillable = [
        'conductor_id',
        'vehiculo_id',
        'ruta_id',
        'fecha_salida',
        'hora_salida',
        'fecha_estimada_llegada',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'fecha_salida'           => 'date:Y-m-d',
        'fecha_estimada_llegada' => 'date:Y-m-d',
    ];
}