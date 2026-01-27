<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('solicituds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('tipo_solicitud_id')->constrained()->onDelete('restrict');
            $table->foreignId('estado_actual_id')->nullable()->constrained('estados')->onDelete('set null');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_envio')->nullable();
            $table->text('descripcion')->nullable();
            $table->text('observaciones_secretaria')->nullable();
            $table->text('observaciones_decano')->nullable();
            $table->timestamps();
            
            $table->index('student_id');
            $table->index('tipo_solicitud_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('solicituds');
    }
};