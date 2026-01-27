<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Student::with('user');
        
        // Motor de búsqueda
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            })->orWhere('carrera', 'LIKE', "%{$search}%");
        }
        
        $students = $query->paginate(10);
        
        return view('students.index', compact('students'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('students.create');
    }

    /**
     * Store a newly created resource in storage.
     */
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

        // Crear usuario
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        // Crear estudiante
        Student::create([
            'user_id' => $user->id,
            'carrera' => $validated['carrera'],
            'anio' => $validated['anio'],
        ]);

        return redirect()->route('students.index')
            ->with('success', 'Estudiante registrado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        $student->load('user', 'solicitudes.tipoSolicitud', 'solicitudes.estadoActual');
        return view('students.show', compact('student'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student)
    {
        $student->load('user');
        return view('students.edit', compact('student'));
    }

    /**
     * Update the specified resource in storage.
     */
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

        // Actualizar usuario
        $student->user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'is_active' => $validated['is_active'],
        ]);

        // Actualizar estudiante
        $student->update([
            'carrera' => $validated['carrera'],
            'anio' => $validated['anio'],
        ]);

        return redirect()->route('students.index')
            ->with('success', 'Estudiante actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        $student->user->delete();
        $student->delete();

        return redirect()->route('students.index')
            ->with('success', 'Estudiante eliminado exitosamente.');
    }
}