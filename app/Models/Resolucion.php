<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resolucion extends Model
{
    use HasFactory;

    protected $fillable = [
        'validacion_id',
        'emisor_id',
        'fecha_resolucion',
        'contenido',
        'decision',
        'archivo_pdf',
    ];

    protected $casts = [
        'fecha_resolucion' => 'datetime',
    ];

    public function validacion()
    {
        return $this->belongsTo(Validacion::class);
    }

    public function emisor()
    {
        return $this->belongsTo(User::class, 'emisor_id');
    }
}