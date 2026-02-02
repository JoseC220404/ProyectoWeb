<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Solicitud extends Model
{
    protected $table = 'solicituds';

    protected $fillable = [
        'student_id',
        'tipo_solicitud_id',
        'descripcion',
        'estado_actual_id',
        'fecha_envio',
        'observaciones_secretaria',
        'observaciones_decano',
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function tipoSolicitud(): BelongsTo
    {
        return $this->belongsTo(TipoSolicitud::class, 'tipo_solicitud_id');
    }

    public function estadoActual(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'estado_actual_id');
    }

    // Historial de cambios (NO es tabla pivote, es tabla de historial)
    public function historial(): HasMany
    {
        return $this->hasMany(HistorialEstado::class)->orderBy('fecha_cambio', 'desc');
    }

    public function archivosAdjuntos(): HasMany
    {
        return $this->hasMany(ArchivoAdjunto::class);
    }

    public function validaciones(): HasMany
    {
        return $this->hasMany(Validacion::class);
    }
}