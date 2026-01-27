<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('resolucions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('validacion_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('emisor_id')->constrained('users')->onDelete('restrict');
            $table->timestamp('fecha_resolucion')->useCurrent();
            $table->text('contenido')->nullable();
            $table->string('decision');
            $table->string('archivo_pdf')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('resolucions');
    }
};