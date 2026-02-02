<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoSolicitud extends Model
{
    protected $table = 'tipo_solicituds';
    
    protected $fillable = ['nombre', 'descripcion', 'disponible'];

    /**
     * Una solicitud de tipo tiene muchas solicitudes
     */
    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'tipo_solicitud_id');
    }
}