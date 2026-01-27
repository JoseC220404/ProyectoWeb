<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('historial_estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained()->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict');
            $table->string('estado_anterior')->nullable();
            $table->string('estado_nuevo');
            $table->timestamp('fecha_cambio')->useCurrent();
            $table->text('observacion')->nullable();
            $table->foreignId('estado_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            
            $table->index('solicitud_id');
            $table->index('usuario_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('historial_estados');
    }
};