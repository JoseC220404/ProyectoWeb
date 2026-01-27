<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Validacion extends Model
{
    use HasFactory;

    protected $fillable = [
        'solicitud_id',
        'secretaria_id',
        'fecha_validacion',
        'estado_validacion',
        'comentarios',
    ];

    protected $casts = [
        'fecha_validacion' => 'datetime',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function secretaria()
    {
        return $this->belongsTo(User::class, 'secretaria_id');
    }

    public function resolucion()
    {
        return $this->hasOne(Resolucion::class);
    }
}