<?php
// En database/migrations/2026_01_10_173453_create_users_table.php
// CAMBIA de 'create' a 'table' para modificar la tabla existente:

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // En lugar de crear la tabla, modificamos la existente
        Schema::table('users', function (Blueprint $table) {
            // Agregar las columnas que faltan a la tabla users de Laravel
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');
            $table->boolean('is_active')->default(true)->after('password');
            $table->foreignId('role_id')->nullable()->after('is_active')->constrained()->onDelete('set null');
            
            // Renombrar la columna 'name' si existe
            if (Schema::hasColumn('users', 'name')) {
                // Primero dividir el name en first_name y last_name si hay datos
                // Luego eliminar la columna name
                $table->dropColumn('name');
            }
        });

        // Estas tablas ya existen en Laravel, pero las creamos por si acaso
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'is_active', 'role_id']);
            // Restaurar columna name
            $table->string('name')->nullable()->after('id');
        });
    }
};