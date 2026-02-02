<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\Student;
use App\Models\TipoSolicitud;
use App\Models\Estado;
use App\Models\HistorialEstado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SolicitudController extends Controller
{
    public function index(Request $request)
    {
        $query = Solicitud::with(['student.user', 'tipoSolicitud', 'estadoActual']);

        if ($request->filled('student')) {
            $query->whereHas('student.user', function($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->student}%")
                  ->orWhere('last_name', 'like', "%{$request->student}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo_solicitud_id', $request->tipo);
        }

        $solicitudes = $query->latest()->paginate(15)->withQueryString();
        $tipos = TipoSolicitud::all();

        return view('solicitudes.index', compact('solicitudes', 'tipos'));
    }

    public function create()
    {
        $students = Student::with('user')->get();
        $tipos = TipoSolicitud::all();
        return view('solicitudes.create', compact('students', 'tipos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'tipo_solicitud_id' => 'required|exists:tipo_solicituds,id',
            'descripcion' => 'required|string|min:10',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $estadoInicial = Estado::where('nombre', 'Pendiente')->firstOrFail();
                
                $solicitud = Solicitud::create([
                    'student_id' => $validated['student_id'],
                    'tipo_solicitud_id' => $validated['tipo_solicitud_id'],
                    'descripcion' => $validated['descripcion'],
                    'estado_actual_id' => $estadoInicial->id,
                    'fecha_envio' => now(),
                ]);
                
                HistorialEstado::create([
                    'solicitud_id' => $solicitud->id,
                    'usuario_id' => auth()->id(),
                    'estado_anterior' => null,
                    'estado_nuevo' => $estadoInicial->nombre,
                    'estado_id' => $estadoInicial->id,
                    'fecha_cambio' => now(),
                    'observacion' => 'Solicitud creada',
                ]);
            });

            return redirect()->route('solicitudes.index')->with('success', 'Solicitud creada con éxito.');
        } catch (\Exception $e) {
            Log::error('Error creando solicitud: ' . $e->getMessage());
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Solicitud $solicitud)
    {
        $solicitud->load(['student.user', 'tipoSolicitud', 'estadoActual', 'historial.usuario']);
        return view('solicitudes.show', compact('solicitud'));
    }

    public function edit(Solicitud $solicitud)
    {
        Log::info('Editando solicitud ID: ' . $solicitud->id . ' Estado actual: ' . ($solicitud->estadoActual->nombre ?? 'N/A'));
        
        if ($solicitud->estadoActual->nombre !== 'Pendiente') {
            return redirect()->route('solicitudes.index')
                ->with('error', 'No se puede editar. Estado actual: ' . $solicitud->estadoActual->nombre);
        }

        $students = Student::with('user')->get();
        $tipos = TipoSolicitud::all();
        return view('solicitudes.edit', compact('solicitud', 'students', 'tipos'));
    }

    public function update(Request $request, Solicitud $solicitud)
    {
        Log::info('Intentando UPDATE solicitud ID: ' . $solicitud->id);
        
        if ($solicitud->estadoActual->nombre !== 'Pendiente') {
            return redirect()->route('solicitudes.index')
                ->with('error', 'No se puede modificar una solicitud en proceso.');
        }

        try {
            $validated = $request->validate([
                'tipo_solicitud_id' => 'required|exists:tipo_solicituds,id',
                'descripcion' => 'required|string|min:10',
            ]);

            Log::info('Validación pasada. Datos:', $validated);

            $result = $solicitud->update($validated);
            
            Log::info('Resultado update: ' . ($result ? 'EXITOSO' : 'FALLIDO'));
            Log::info('Nuevos datos: ' . json_encode($solicitud->fresh()->toArray()));

            return redirect()->route('solicitudes.index')
                ->with('success', 'Solicitud actualizada correctamente.');

        } catch (\Exception $e) {
            Log::error('Error en update: ' . $e->getMessage());
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage())->withInput();
        }
    }

    public function updateEstado(Request $request, Solicitud $solicitud)
    {
        Log::info('=======================');
        Log::info('CAMBIO DE ESTADO INICIADO');
        Log::info('Solicitud ID: ' . $solicitud->id);
        Log::info('Datos recibidos: ' . json_encode($request->all()));
        Log::info('Usuario: ' . auth()->id());

        try {
            $request->validate([
                'estado_id' => 'required|exists:estados,id',
                'observaciones' => 'nullable|string|max:1000'
            ]);

            $nuevoEstado = Estado::find($request->estado_id);
            
            if (!$nuevoEstado) {
                Log::error('Estado no encontrado ID: ' . $request->estado_id);
                return back()->with('error', 'Estado no encontrado');
            }

            Log::info('Nuevo estado: ' . $nuevoEstado->nombre);
            
            $estadoAnterior = $solicitud->estadoActual;
            
            // Prevenir cambio al mismo estado
            if ($estadoAnterior && $estadoAnterior->id == $nuevoEstado->id) {
                Log::warning('Intento de cambiar al mismo estado');
                return back()->with('error', 'La solicitud ya está en ese estado.');
            }

            DB::transaction(function () use ($solicitud, $estadoAnterior, $nuevoEstado, $request) {
                
                // 1. Actualizar campo estado_actual_id
                $updateResult = $solicitud->update(['estado_actual_id' => $nuevoEstado->id]);
                Log::info('Update estado_actual_id: ' . ($updateResult ? 'OK' : 'FALLÓ'));
                
                // 2. Crear historial
                $historial = HistorialEstado::create([
                    'solicitud_id' => $solicitud->id,
                    'usuario_id' => auth()->id(),
                    'estado_anterior' => $estadoAnterior ? $estadoAnterior->nombre : null,
                    'estado_nuevo' => $nuevoEstado->nombre,
                    'estado_id' => $nuevoEstado->id,
                    'fecha_cambio' => now(),
                    'observacion' => $request->observaciones ?? 'Cambio de estado',
                ]);
                
                Log::info('Historial creado ID: ' . $historial->id);
            });

            Log::info('CAMBIO DE ESTADO EXITOSO');
            return back()->with('success', 'Estado cambiado a: ' . $nuevoEstado->nombre);

        } catch (\Exception $e) {
            Log::error('ERROR CAMBIO ESTADO: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy(Solicitud $solicitud)
    {
        if ($solicitud->estadoActual->nombre !== 'Pendiente') {
            return redirect()->route('solicitudes.index')->with('error', 'No se puede eliminar una solicitud en proceso.');
        }

        try {
            $solicitud->delete();
            return redirect()->route('solicitudes.index')->with('success', 'Solicitud eliminada.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }
}