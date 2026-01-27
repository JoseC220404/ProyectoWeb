<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Solicitud;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalEstudiantes = Student::count();
        $totalSolicitudes = Solicitud::count();
        $solicitudesNuevas = Solicitud::where('created_at', '>=', now()->subDay())->count();
        
        return view('dashboard', compact('totalEstudiantes', 'totalSolicitudes', 'solicitudesNuevas'));
    }
}