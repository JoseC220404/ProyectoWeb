<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Estado;
use App\Models\TipoSolicitud;
use App\Models\Solicitud;
use App\Models\HistorialEstado;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            
            // 1. ROLES (Básicos)
            $adminRole = Role::firstOrCreate(['name' => 'Administrador'], ['description' => 'Acceso total']);
            $secretariaRole = Role::firstOrCreate(['name' => 'Secretaría'], ['description' => 'Gestiona solicitudes']);
            $estudianteRole = Role::firstOrCreate(['name' => 'Estudiante'], ['description' => 'Crea solicitudes']);
            
            $this->command->info('✓ Roles creados');

            // 2. ESTADOS (CRÍTICO - IDs fijos para evitar confusiones)
            $estados = [
                ['id' => 1, 'nombre' => 'Pendiente', 'descripcion' => 'Solicitud recién creada'],
                ['id' => 2, 'nombre' => 'En revisión', 'descripcion' => 'En proceso de validación'],
                ['id' => 3, 'nombre' => 'Aprobado', 'descripcion' => 'Solicitud aprobada'],
                ['id' => 4, 'nombre' => 'Rechazado', 'descripcion' => 'Solicitud rechazada'],
            ];
            
            foreach ($estados as $estado) {
                Estado::firstOrCreate(['id' => $estado['id']], $estado);
            }
            
            $this->command->info('✓ Estados creados (Pendiente, En revisión, Aprobado, Rechazado)');

            // 3. TIPOS DE SOLICITUD (Ejemplos reales universidad)
            $tipos = [
                ['nombre' => 'Solicitud de Título', 'descripcion' => 'Para iniciar trámite de título universitario', 'disponible' => true],
                ['nombre' => 'Cambio de Carrera', 'descripcion' => 'Solicitud para cambio de especialidad', 'disponible' => true],
                ['nombre' => 'Revisión de Notas', 'descripcion' => 'Reclamación sobre calificación', 'disponible' => true],
                ['nombre' => 'Justificante de Inasistencia', 'descripcion' => 'Justificación médica u otra', 'disponible' => true],
                ['nombre' => 'Prórroga de Estudios', 'descripcion' => 'Extensión de tiempo de carrera', 'disponible' => true],
            ];
            
            foreach ($tipos as $tipo) {
                TipoSolicitud::firstOrCreate(['nombre' => $tipo['nombre']], $tipo);
            }
            
            $this->command->info('✓ Tipos de solicitud creados');

            // 4. USUARIOS DE PRUEBA
            
            // 4.1 Administrador
            $admin = User::firstOrCreate(
                ['email' => 'admin@uclv.edu.cu'],
                [
                    'first_name' => 'Administrador',
                    'last_name' => 'Sistema',
                    'password' => Hash::make('password'),
                    'role_id' => $adminRole->id,
                    'is_active' => true,
                ]
            );
            
            // 4.2 Secretaria
            $secretaria = User::firstOrCreate(
                ['email' => 'secretaria@uclv.edu.cu'],
                [
                    'first_name' => 'María',
                    'last_name' => 'González',
                    'password' => Hash::make('password'),
                    'role_id' => $secretariaRole->id,
                    'is_active' => true,
                ]
            );
            
            // 4.3 Estudiante 1
            $est1 = User::firstOrCreate(
                ['email' => 'estudiante1@uclv.edu.cu'],
                [
                    'first_name' => 'Juan',
                    'last_name' => 'Pérez Rodríguez',
                    'password' => Hash::make('password'),
                    'role_id' => $estudianteRole->id,
                    'is_active' => true,
                ]
            );
            
            $student1 = Student::firstOrCreate(
                ['user_id' => $est1->id],
                [
                    'carrera' => 'Ingeniería Informática',
                    'anio' => 3,
                ]
            );
            
            // 4.4 Estudiante 2
            $est2 = User::firstOrCreate(
                ['email' => 'estudiante2@uclv.edu.cu'],
                [
                    'first_name' => 'Ana',
                    'last_name' => 'López Martínez',
                    'password' => Hash::make('password'),
                    'role_id' => $estudianteRole->id,
                    'is_active' => true,
                ]
            );
            
            $student2 = Student::firstOrCreate(
                ['user_id' => $est2->id],
                [
                    'carrera' => 'Medicina',
                    'anio' => 4,
                ]
            );
            
            $this->command->info('✓ Usuarios creados:');
            $this->command->info('  - admin@uclv.edu.cu / password');
            $this->command->info('  - secretaria@uclv.edu.cu / password');
            $this->command->info('  - estudiante1@uclv.edu.cu / password');
            $this->command->info('  - estudiante2@uclv.edu.cu / password');

            // 5. SOLICITUDES DE EJEMPLO (para probar el flujo)
            
            // Solicitud 1: Pendiente (para probar el botón Check)
            $solicitud1 = Solicitud::create([
                'student_id' => $student1->id,
                'tipo_solicitud_id' => 1, // Solicitud de Título
                'descripcion' => 'Solicito iniciar el trámite para la obtención del título de Ingeniero Informático. Adjunto toda la documentación requerida.',
                'estado_actual_id' => 1, // Pendiente
                'fecha_envio' => now()->subDays(2),
            ]);
            
            HistorialEstado::create([
                'solicitud_id' => $solicitud1->id,
                'usuario_id' => $est1->id,
                'estado_anterior' => null,
                'estado_nuevo' => 'Pendiente',
                'estado_id' => 1,
                'fecha_cambio' => now()->subDays(2),
                'observacion' => 'Solicitud creada por estudiante',
            ]);
            
            // Solicitud 2: En revisión (ya pasó por pendiente)
            $solicitud2 = Solicitud::create([
                'student_id' => $student2->id,
                'tipo_solicitud_id' => 2, // Cambio de Carrera
                'descripcion' => 'Solicito cambio de carrera de Medicina a Farmacia por motivos personales.',
                'estado_actual_id' => 2, // En revisión
                'fecha_envio' => now()->subDays(5),
            ]);
            
            HistorialEstado::create([
                'solicitud_id' => $solicitud2->id,
                'usuario_id' => $est1->id,
                'estado_anterior' => null,
                'estado_nuevo' => 'Pendiente',
                'estado_id' => 1,
                'fecha_cambio' => now()->subDays(5),
                'observacion' => 'Solicitud creada',
            ]);
            
            HistorialEstado::create([
                'solicitud_id' => $solicitud2->id,
                'usuario_id' => $secretaria->id,
                'estado_anterior' => 'Pendiente',
                'estado_nuevo' => 'En revisión',
                'estado_id' => 2,
                'fecha_cambio' => now()->subDays(3),
                'observacion' => 'Revisión iniciada por secretaría',
            ]);
            
            // Solicitud 3: Aprobada (para ver historial completo)
            $solicitud3 = Solicitud::create([
                'student_id' => $student1->id,
                'tipo_solicitud_id' => 3, // Revisión de Notas
                'descripcion' => 'Solicito revisión de la nota del examen final de Base de Datos.',
                'estado_actual_id' => 3, // Aprobado
                'fecha_envio' => now()->subDays(10),
            ]);
            
            // Historial completo de esta solicitud
            HistorialEstado::create([
                'solicitud_id' => $solicitud3->id,
                'usuario_id' => $est1->id,
                'estado_anterior' => null,
                'estado_nuevo' => 'Pendiente',
                'estado_id' => 1,
                'fecha_cambio' => now()->subDays(10),
                'observacion' => 'Solicitud creada',
            ]);
            
            HistorialEstado::create([
                'solicitud_id' => $solicitud3->id,
                'usuario_id' => $secretaria->id,
                'estado_anterior' => 'Pendiente',
                'estado_nuevo' => 'En revisión',
                'estado_id' => 2,
                'fecha_cambio' => now()->subDays(8),
                'observacion' => 'Enviado a departamento académico',
            ]);
            
            HistorialEstado::create([
                'solicitud_id' => $solicitud3->id,
                'usuario_id' => $secretaria->id,
                'estado_anterior' => 'En revisión',
                'estado_nuevo' => 'Aprobado',
                'estado_id' => 3,
                'fecha_cambio' => now()->subDays(5),
                'observacion' => 'Revisión favorable. Se aprueba cambio de nota.',
            ]);
            
            // Solicitud 4: Rechazada
            $solicitud4 = Solicitud::create([
                'student_id' => $student2->id,
                'tipo_solicitud_id' => 4, // Justificante
                'descripcion' => 'Solicito justificación por inasistencia del 15 de enero por motivos personales.',
                'estado_actual_id' => 4, // Rechazado
                'fecha_envio' => now()->subDays(7),
            ]);
            
            HistorialEstado::create([
                'solicitud_id' => $solicitud4->id,
                'usuario_id' => $est2->id,
                'estado_anterior' => null,
                'estado_nuevo' => 'Pendiente',
                'estado_id' => 1,
                'fecha_cambio' => now()->subDays(7),
                'observacion' => 'Solicitud creada',
            ]);
            
            HistorialEstado::create([
                'solicitud_id' => $solicitud4->id,
                'usuario_id' => $secretaria->id,
                'estado_anterior' => 'Pendiente',
                'estado_nuevo' => 'Rechazado',
                'estado_id' => 4,
                'fecha_cambio' => now()->subDays(4),
                'observacion' => 'No se presentó documentación médica que sustente la inasistencia.',
            ]);
            
            $this->command->info('✓ Solicitudes de prueba creadas:');
            $this->command->info('  - 1 Pendiente (para probar botón Check)');
            $this->command->info('  - 1 En revisión');
            $this->command->info('  - 1 Aprobada (con historial completo)');
            $this->command->info('  - 1 Rechazada');
            
        });

        $this->command->info('');
        $this->command->info('🎉 SEEDING COMPLETADO');
        $this->command->info('Ahora puedes iniciar sesión con cualquiera de estos usuarios:');
        $this->command->info('Contraseña para todos: password');
    }
}