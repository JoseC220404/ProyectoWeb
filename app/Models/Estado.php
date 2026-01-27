<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion'];

    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'estado_actual_id');
    }

    public function historiales()
    {
        return $this->hasMany(HistorialEstado::class);
    }
}