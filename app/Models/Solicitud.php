<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'tipo_solicitud_id',
        'estado_actual_id',
        'fecha_creacion',
        'fecha_envio',
        'descripcion',
        'observaciones_secretaria',
        'observaciones_decano',
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_envio' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function tipoSolicitud()
    {
        return $this->belongsTo(TipoSolicitud::class);
    }

    public function estadoActual()
    {
        return $this->belongsTo(Estado::class, 'estado_actual_id');
    }

    public function validacion()
    {
        return $this->hasOne(Validacion::class);
    }

    public function archivos()
    {
        return $this->hasMany(ArchivoAdjunto::class);
    }

    public function historiales()
    {
        return $this->hasMany(HistorialEstado::class);
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class);
    }
}