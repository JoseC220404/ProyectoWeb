<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function solicitudesComoEstudiante()
    {
        return $this->hasManyThrough(Solicitud::class, Student::class);
    }

    // Si un usuario puede ser secretaria o emisor en validaciones/resoluciones
    public function validaciones()
    {
        return $this->hasMany(Validacion::class, 'secretaria_id');
    }

    public function resoluciones()
    {
        return $this->hasMany(Resolucion::class, 'emisor_id');
    }

    public function historiales()
    {
        return $this->hasMany(HistorialEstado::class, 'usuario_id');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'usuario_destino_id');
    }
}