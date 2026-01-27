<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoSolicitud extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion', 'disponible'];

    protected $casts = [
        'disponible' => 'boolean',
    ];

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class);
    }
}