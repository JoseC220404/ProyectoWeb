<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notificacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_destino_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('solicitud_id')->constrained()->onDelete('cascade');
            $table->text('mensaje');
            $table->timestamp('fecha_envio')->useCurrent();
            $table->boolean('leida')->default(false);
            $table->timestamps();
            
            $table->index('usuario_destino_id');
            $table->index('solicitud_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notificacions');
    }
};
