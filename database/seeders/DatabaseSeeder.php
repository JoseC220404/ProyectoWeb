<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Student;
use App\Models\TipoSolicitud;
use App\Models\Estado;
use App\Models\Solicitud;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Crear roles
        $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrador']);
        $estudianteRole = Role::create(['name' => 'estudiante', 'description' => 'Estudiante']);
        $secretariaRole = Role::create(['name' => 'secretaria', 'description' => 'Secretaría']);
        $decanoRole = Role::create(['name' => 'decano', 'description' => 'Decano']);

        // Crear usuario administrador
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'UCLV',
            'email' => 'admin@uclv.edu.cu',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
        ]);

        // Crear usuario secretaria
        $secretaria = User::create([
            'first_name' => 'Maria',
            'last_name' => 'Secretaria',
            'email' => 'secretaria@uclv.edu.cu',
            'password' => Hash::make('password'),
            'role_id' => $secretariaRole->id,
        ]);

        // Crear usuario decano
        $decano = User::create([
            'first_name' => 'Dr. Juan',
            'last_name' => 'Decano',
            'email' => 'decano@uclv.edu.cu',
            'password' => Hash::make('password'),
            'role_id' => $decanoRole->id,
        ]);

        // Crear estudiantes
        $estudiantes = [
            [
                'first_name' => 'Carlos',
                'last_name' => 'González',
                'email' => 'carlos@estudiante.uclv.edu.cu',
                'carrera' => 'Ingeniería Informática',
                'anio' => 3,
            ],
            [
                'first_name' => 'Ana',
                'last_name' => 'Pérez',
                'email' => 'ana@estudiante.uclv.edu.cu',
                'carrera' => 'Licenciatura en Derecho',
                'anio' => 4,
            ],
        ];

        foreach ($estudiantes as $est) {
            $user = User::create([
                'first_name' => $est['first_name'],
                'last_name' => $est['last_name'],
                'email' => $est['email'],
                'password' => Hash::make('password'),
                'role_id' => $estudianteRole->id,
            ]);

            Student::create([
                'user_id' => $user->id,
                'carrera' => $est['carrera'],
                'anio' => $est['anio'],
            ]);
        }

        // Crear estados
        $estados = [
            ['nombre' => 'Pendiente', 'descripcion' => 'Solicitud en espera'],
            ['nombre' => 'En revisión', 'descripcion' => 'En proceso de revisión'],
            ['nombre' => 'Aprobada', 'descripcion' => 'Solicitud aprobada'],
            ['nombre' => 'Rechazada', 'descripcion' => 'Solicitud rechazada'],
        ];

        foreach ($estados as $estado) {
            Estado::create($estado);
        }

        // Crear tipos de solicitud
        $tipos = [
            ['nombre' => 'Certificado', 'descripcion' => 'Certificado académico', 'disponible' => true],
            ['nombre' => 'Matrícula', 'descripcion' => 'Solicitud de matrícula', 'disponible' => true],
            ['nombre' => 'Beca', 'descripcion' => 'Solicitud de beca', 'disponible' => true],
            ['nombre' => 'Traslado', 'descripcion' => 'Solicitud de traslado', 'disponible' => false],
        ];

        foreach ($tipos as $tipo) {
            TipoSolicitud::create($tipo);
        }

        // Crear algunas solicitudes
        $student1 = Student::first();
        $tipo1 = TipoSolicitud::first();
        $estado1 = Estado::first();

        Solicitud::create([
            'student_id' => $student1->id,
            'tipo_solicitud_id' => $tipo1->id,
            'estado_actual_id' => $estado1->id,
            'descripcion' => 'Necesito un certificado de estudios para una beca externa.',
        ]);
    }
}