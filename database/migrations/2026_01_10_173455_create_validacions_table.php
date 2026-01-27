<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('validacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained()->onDelete('cascade');
            $table->foreignId('secretaria_id')->constrained('users')->onDelete('restrict');
            $table->timestamp('fecha_validacion')->useCurrent();
            $table->string('estado_validacion');
            $table->text('comentarios')->nullable();
            $table->timestamps();
            
            $table->index('solicitud_id');
            $table->index('secretaria_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('validacions');
    }
};