<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\Student;
use App\Models\TipoSolicitud;
use App\Models\Estado;
use App\Models\ArchivoAdjunto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SolicitudController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Solicitud::with(['student.user', 'tipoSolicitud', 'estadoActual'])
            ->withCount('archivos');
        
        // Filtros
        if ($request->has('estado_id') && !empty($request->estado_id)) {
            $query->where('estado_actual_id', $request->estado_id);
        }
        
        if ($request->has('tipo_solicitud_id') && !empty($request->tipo_solicitud_id)) {
            $query->where('tipo_solicitud_id', $request->tipo_solicitud_id);
        }
        
        if ($request->has('fecha_desde') && !empty($request->fecha_desde)) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        
        if ($request->has('fecha_hasta') && !empty($request->fecha_hasta)) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }
        
        if ($request->has('estudiante') && !empty($request->estudiante)) {
            $query->whereHas('student.user', function($q) use ($request) {
                $q->where('first_name', 'LIKE', "%{$request->estudiante}%")
                  ->orWhere('last_name', 'LIKE', "%{$request->estudiante}%");
            });
        }
        
        // Ordenamiento
        $orderBy = $request->get('order_by', 'created_at');
        $orderDirection = $request->get('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);
        
        $solicitudes = $query->paginate(15)->withQueryString();
        
        $tiposSolicitud = TipoSolicitud::where('disponible', true)->get();
        $estados = Estado::all();
        
        // Si se solicita exportar a Excel
        if ($request->has('export') && $request->export == 'excel') {
            return $this->exportarExcel($solicitudes);
        }
        
        return view('solicitudes.index', compact('solicitudes', 'tiposSolicitud', 'estados'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $students = Student::with('user')->get();
        $tiposSolicitud = TipoSolicitud::where('disponible', true)->get();
        $estados = Estado::all();
        
        return view('solicitudes.create', compact('students', 'tiposSolicitud', 'estados'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'tipo_solicitud_id' => 'required|exists:tipo_solicituds,id',
            'estado_actual_id' => 'required|exists:estados,id',
            'descripcion' => 'required|string|min:10|max:1000',
            'archivos.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        // Crear solicitud
        $solicitud = Solicitud::create([
            'student_id' => $validated['student_id'],
            'tipo_solicitud_id' => $validated['tipo_solicitud_id'],
            'estado_actual_id' => $validated['estado_actual_id'],
            'descripcion' => $validated['descripcion'],
            'fecha_creacion' => now(),
        ]);

        // Subir archivos adjuntos
        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $archivo) {
                $nombreOriginal = $archivo->getClientOriginalName();
                $ruta = $archivo->store('solicitudes/' . $solicitud->id, 'public');
                
                ArchivoAdjunto::create([
                    'solicitud_id' => $solicitud->id,
                    'nombre' => $nombreOriginal,
                    'ruta' => $ruta,
                    'fecha_subida' => now(),
                ]);
            }
        }

        // Crear historial
        \App\Models\HistorialEstado::create([
            'solicitud_id' => $solicitud->id,
            'usuario_id' => auth()->id(),
            'estado_anterior' => null,
            'estado_nuevo' => $solicitud->estadoActual->nombre,
            'observacion' => 'Solicitud creada',
        ]);

        // Crear notificación para el estudiante
        $estudiante = Student::find($validated['student_id']);
        \App\Models\Notificacion::create([
            'usuario_destino_id' => $estudiante->user_id,
            'solicitud_id' => $solicitud->id,
            'mensaje' => 'Se ha creado una nueva solicitud para usted.',
            'fecha_envio' => now(),
            'leida' => false,
        ]);

        return redirect()->route('solicitudes.show', $solicitud)
            ->with('success', 'Solicitud creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Solicitud $solicitud)
    {
        $solicitud->load([
            'student.user',
            'tipoSolicitud',
            'estadoActual',
            'archivos',
            'historiales.usuario',
            'validacion.secretaria',
            'validacion.resolucion.emisor'
        ]);
        
        return view('solicitudes.show', compact('solicitud'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Solicitud $solicitud)
    {
        $solicitud->load(['student.user', 'tipoSolicitud', 'estadoActual', 'archivos']);
        $tiposSolicitud = TipoSolicitud::where('disponible', true)->get();
        $estados = Estado::all();
        
        return view('solicitudes.edit', compact('solicitud', 'tiposSolicitud', 'estados'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Solicitud $solicitud)
    {
        $validated = $request->validate([
            'descripcion' => 'required|string|min:10|max:1000',
            'estado_actual_id' => 'required|exists:estados,id',
            'observaciones_secretaria' => 'nullable|string|max:1000',
            'observaciones_decano' => 'nullable|string|max:1000',
            'archivos.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        // Guardar estado anterior
        $estadoAnterior = $solicitud->estadoActual->nombre;
        
        // Actualizar solicitud
        $solicitud->update([
            'descripcion' => $validated['descripcion'],
            'estado_actual_id' => $validated['estado_actual_id'],
            'observaciones_secretaria' => $validated['observaciones_secretaria'] ?? $solicitud->observaciones_secretaria,
            'observaciones_decano' => $validated['observaciones_decano'] ?? $solicitud->observaciones_decano,
        ]);

        // Subir nuevos archivos
        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $archivo) {
                $nombreOriginal = $archivo->getClientOriginalName();
                $ruta = $archivo->store('solicitudes/' . $solicitud->id, 'public');
                
                ArchivoAdjunto::create([
                    'solicitud_id' => $solicitud->id,
                    'nombre' => $nombreOriginal,
                    'ruta' => $ruta,
                    'fecha_subida' => now(),
                ]);
            }
        }

        // Crear historial si cambió el estado
        if ($estadoAnterior != $solicitud->estadoActual->nombre) {
            \App\Models\HistorialEstado::create([
                'solicitud_id' => $solicitud->id,
                'usuario_id' => auth()->id(),
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $solicitud->estadoActual->nombre,
                'observacion' => $request->observacion_cambio ?? 'Cambio de estado',
            ]);

            // Crear notificación para el estudiante
            \App\Models\Notificacion::create([
                'usuario_destino_id' => $solicitud->student->user_id,
                'solicitud_id' => $solicitud->id,
                'mensaje' => 'El estado de su solicitud ha cambiado a: ' . $solicitud->estadoActual->nombre,
                'fecha_envio' => now(),
                'leida' => false,
            ]);
        }

        return redirect()->route('solicitudes.show', $solicitud)
            ->with('success', 'Solicitud actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Solicitud $solicitud)
    {
        // Eliminar archivos adjuntos
        foreach ($solicitud->archivos as $archivo) {
            Storage::disk('public')->delete($archivo->ruta);
            $archivo->delete();
        }
        
        // Eliminar historiales
        $solicitud->historiales()->delete();
        
        // Eliminar notificaciones
        $solicitud->notificaciones()->delete();
        
        // Eliminar validación si existe
        if ($solicitud->validacion) {
            if ($solicitud->validacion->resolucion) {
                if ($solicitud->validacion->resolucion->archivo_pdf) {
                    Storage::disk('public')->delete($solicitud->validacion->resolucion->archivo_pdf);
                }
                $solicitud->validacion->resolucion()->delete();
            }
            $solicitud->validacion()->delete();
        }
        
        $solicitud->delete();
        
        return redirect()->route('solicitudes.index')
            ->with('success', 'Solicitud eliminada exitosamente.');
    }

    /**
     * Cambiar estado de la solicitud
     */
    public function cambiarEstado(Request $request, Solicitud $solicitud)
    {
        $request->validate([
            'estado_id' => 'required|exists:estados,id',
            'observacion' => 'nullable|string|max:500',
        ]);

        $estadoAnterior = $solicitud->estadoActual->nombre;
        $nuevoEstado = Estado::find($request->estado_id);
        
        $solicitud->update(['estado_actual_id' => $request->estado_id]);

        // Crear historial
        \App\Models\HistorialEstado::create([
            'solicitud_id' => $solicitud->id,
            'usuario_id' => auth()->id(),
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $nuevoEstado->nombre,
            'observacion' => $request->observacion,
        ]);

        // Crear notificación para el estudiante
        \App\Models\Notificacion::create([
            'usuario_destino_id' => $solicitud->student->user_id,
            'solicitud_id' => $solicitud->id,
            'mensaje' => 'El estado de su solicitud ha cambiado a: ' . $nuevoEstado->nombre,
            'fecha_envio' => now(),
            'leida' => false,
        ]);

        return redirect()->back()
            ->with('success', 'Estado actualizado exitosamente.');
    }

    /**
     * Descargar archivo adjunto
     */
    public function descargarArchivo(ArchivoAdjunto $archivo)
    {
        if (!Storage::disk('public')->exists($archivo->ruta)) {
            return redirect()->back()->with('error', 'El archivo no existe.');
        }

        return Storage::disk('public')->download($archivo->ruta, $archivo->nombre);
    }

    /**
     * Eliminar archivo adjunto
     */
    public function eliminarArchivo(ArchivoAdjunto $archivo)
    {
        $solicitudId = $archivo->solicitud_id;
        
        Storage::disk('public')->delete($archivo->ruta);
        $archivo->delete();
        
        return redirect()->route('solicitudes.show', $solicitudId)
            ->with('success', 'Archivo eliminado exitosamente.');
    }

    /**
     * Obtener historial de la solicitud para AJAX
     */
    public function historial(Solicitud $solicitud)
    {
        $historial = $solicitud->historiales()
            ->with('usuario:id,first_name,last_name')
            ->orderBy('fecha_cambio', 'desc')
            ->get()
            ->map(function($item) {
                return [
                    'estado_nuevo' => $item->estado_nuevo,
                    'estado_anterior' => $item->estado_anterior,
                    'observacion' => $item->observacion,
                    'fecha_cambio' => $item->fecha_cambio,
                    'usuario' => [
                        'nombre' => $item->usuario->first_name . ' ' . $item->usuario->last_name
                    ]
                ];
            });

        return response()->json([
            'success' => true,
            'historial' => $historial
        ]);
    }

    /**
     * Exportar a Excel
     */
    private function exportarExcel($solicitudes)
    {
        // Implementar exportación a Excel usando una librería como Maatwebsite/Laravel-Excel
        // Por ahora, solo redirigimos de vuelta
        return redirect()->route('solicitudes.index')
            ->with('info', 'La exportación a Excel está en desarrollo.');
    }
}