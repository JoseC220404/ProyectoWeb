<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Solicitud;
use App\Models\TipoSolicitud;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'totalEstudiantes' => Student::count(),
            'totalSolicitudes' => Solicitud::count(),
            'solicitudesNuevas' => Solicitud::whereDate('created_at', today())->count(),
            'solicitudesPendientes' => Solicitud::whereHas('estadoActual', function($q) {
                $q->where('nombre', 'Pendiente');
            })->count(),
            'solicitudesPorTipo' => TipoSolicitud::withCount('solicitudes')->get(),
            'ultimasSolicitudes' => Solicitud::with(['student.user', 'estadoActual'])
                ->latest()
                ->take(5)
                ->get()
        ];
        
        return view('dashboard', $stats);
    }
}