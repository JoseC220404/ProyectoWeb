<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with('user');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            })->orWhere('carrera', 'LIKE', "%{$search}%");
        }
        
        $students = $query->latest()->paginate(10);
        
        return view('students.index', compact('students'));
    }

    public function create()
    {
        return view('students.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'carrera' => 'nullable|string|max:255',
            'anio' => 'nullable|integer|min:1|max:5',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            Student::create([
                'user_id' => $user->id,
                'carrera' => $validated['carrera'],
                'anio' => $validated['anio'],
            ]);
        });

        return redirect()->route('students.index')
            ->with('success', 'Estudiante registrado exitosamente.');
    }

    public function show(Student $student)
    {
        $student->load(['user', 'solicitudes.tipoSolicitud', 'solicitudes.estadoActual']);
        return view('students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $student->load('user');
        return view('students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $student->user_id,
            'carrera' => 'nullable|string|max:255',
            'anio' => 'nullable|integer|min:1|max:5',
            'is_active' => 'required|boolean',
        ]);

        DB::transaction(function () use ($validated, $student) {
            $student->user->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'is_active' => $validated['is_active'],
            ]);

            $student->update([
                'carrera' => $validated['carrera'],
                'anio' => $validated['anio'],
            ]);
        });

        return redirect()->route('students.index')
            ->with('success', 'Estudiante actualizado exitosamente.');
    }

    public function destroy(Student $student)
    {
        DB::transaction(function () use ($student) {
            $user = $student->user;
            $student->delete();
            $user->delete();
        });

        return redirect()->route('students.index')
            ->with('success', 'Estudiante eliminado exitosamente.');
    }
}