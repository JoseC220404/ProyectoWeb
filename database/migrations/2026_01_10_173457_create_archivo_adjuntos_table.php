<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('archivo_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained()->onDelete('cascade');
            $table->string('nombre');
            $table->string('ruta');
            $table->timestamp('fecha_subida')->useCurrent();
            $table->timestamps();
            
            $table->index('solicitud_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('archivo_adjuntos');
    }
};