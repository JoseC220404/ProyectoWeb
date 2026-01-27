<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\Estado;
use App\Models\TipoSolicitud;
use App\Models\HistorialEstado;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PendienteController extends Controller
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
        // Obtener estados "Pendiente" y "En revisión"
        $estadoPendiente = Estado::where('nombre', 'Pendiente')->first();
        $estadoRevision = Estado::where('nombre', 'En revisión')->first();
        
        // Si no existen los estados, redirigir con error
        if (!$estadoPendiente || !$estadoRevision) {
            return redirect()->route('dashboard')
                ->with('error', 'Los estados "Pendiente" o "En revisión" no están configurados en el sistema.');
        }
        
        $query = Solicitud::with(['student.user', 'tipoSolicitud', 'estadoActual'])
            ->whereIn('estado_actual_id', [$estadoPendiente->id, $estadoRevision->id]);
        
        // Filtros
        if ($request->has('tipo_solicitud_id') && !empty($request->tipo_solicitud_id)) {
            $query->where('tipo_solicitud_id', $request->tipo_solicitud_id);
        }
        
        if ($request->has('fecha_desde') && !empty($request->fecha_desde)) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }
        
        if ($request->has('fecha_hasta') && !empty($request->fecha_hasta)) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }
        
        if ($request->has('tiempo_espera') && !empty($request->tiempo_espera)) {
            $horas = (int)$request->tiempo_espera;
            $query->whereRaw("TIMESTAMPDIFF(HOUR, created_at, NOW()) >= ?", [$horas]);
        }
        
        // Ordenar por fecha de creación (más antiguas primero para atender primero)
        $query->orderBy('created_at', 'asc');
        
        $solicitudes = $query->paginate(20)->withQueryString();
        
        // Calcular tiempo de espera para cada solicitud
        foreach ($solicitudes as $solicitud) {
            $solicitud->tiempoEsperaHoras = $solicitud->created_at->diffInHours(now());
        }
        
        // Estadísticas
        $totalPendientes = Solicitud::where('estado_actual_id', $estadoPendiente->id)->count();
        $totalEnRevision = Solicitud::where('estado_actual_id', $estadoRevision->id)->count();
        
        // Obtener tipos de solicitud para el filtro
        $tiposSolicitud = TipoSolicitud::where('disponible', true)->get();
        
        return view('pendientes.index', compact(
            'solicitudes', 
            'totalPendientes', 
            'totalEnRevision',
            'tiposSolicitud'
        ));
    }

    /**
     * Aprobar una solicitud específica
     */
    public function aprobar(Request $request, Solicitud $solicitud)
    {
        // Verificar que la solicitud esté en estado pendiente o en revisión
        $estadosPermitidos = ['Pendiente', 'En revisión'];
        if (!in_array($solicitud->estadoActual->nombre, $estadosPermitidos)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta solicitud no puede ser aprobada en su estado actual.'
            ], 400);
        }

        $estadoAprobada = Estado::where('nombre', 'Aprobada')->first();
        
        if (!$estadoAprobada) {
            return response()->json([
                'success' => false,
                'message' => 'El estado "Aprobada" no está configurado en el sistema.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $estadoAnterior = $solicitud->estadoActual->nombre;
            $solicitud->update(['estado_actual_id' => $estadoAprobada->id]);
            
            // Crear historial
            HistorialEstado::create([
                'solicitud_id' => $solicitud->id,
                'usuario_id' => auth()->id(),
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'Aprobada',
                'observacion' => $request->observacion ?? 'Solicitud aprobada',
                'fecha_cambio' => now(),
            ]);
            
            // Crear notificación para el estudiante
            Notificacion::create([
                'usuario_destino_id' => $solicitud->student->user_id,
                'solicitud_id' => $solicitud->id,
                'mensaje' => 'Su solicitud ha sido aprobada.',
                'fecha_envio' => now(),
                'leida' => false,
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Solicitud aprobada exitosamente.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar una solicitud específica
     */
    public function rechazar(Request $request, Solicitud $solicitud)
    {
        // Verificar que la solicitud esté en estado pendiente o en revisión
        $estadosPermitidos = ['Pendiente', 'En revisión'];
        if (!in_array($solicitud->estadoActual->nombre, $estadosPermitidos)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta solicitud no puede ser rechazada en su estado actual.'
            ], 400);
        }

        $estadoRechazada = Estado::where('nombre', 'Rechazada')->first();
        
        if (!$estadoRechazada) {
            return response()->json([
                'success' => false,
                'message' => 'El estado "Rechazada" no está configurado en el sistema.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $estadoAnterior = $solicitud->estadoActual->nombre;
            $solicitud->update([
                'estado_actual_id' => $estadoRechazada->id,
                'observaciones_secretaria' => $request->motivo,
            ]);
            
            // Crear historial
            HistorialEstado::create([
                'solicitud_id' => $solicitud->id,
                'usuario_id' => auth()->id(),
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'Rechazada',
                'observacion' => $request->motivo ?? 'Solicitud rechazada',
                'fecha_cambio' => now(),
            ]);
            
            // Crear notificación para el estudiante
            Notificacion::create([
                'usuario_destino_id' => $solicitud->student->user_id,
                'solicitud_id' => $solicitud->id,
                'mensaje' => 'Su solicitud ha sido rechazada. Motivo: ' . ($request->motivo ?? 'No especificado'),
                'fecha_envio' => now(),
                'leida' => false,
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Solicitud rechazada exitosamente.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mover una solicitud a revisión
     */
    public function moverRevision(Request $request, Solicitud $solicitud)
    {
        // Solo se puede mover a revisión si está en estado "Pendiente"
        if ($solicitud->estadoActual->nombre !== 'Pendiente') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden mover a revisión las solicitudes en estado "Pendiente".'
            ], 400);
        }

        $estadoRevision = Estado::where('nombre', 'En revisión')->first();
        
        if (!$estadoRevision) {
            return response()->json([
                'success' => false,
                'message' => 'El estado "En revisión" no está configurado en el sistema.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $estadoAnterior = $solicitud->estadoActual->nombre;
            $solicitud->update(['estado_actual_id' => $estadoRevision->id]);
            
            // Crear historial
            HistorialEstado::create([
                'solicitud_id' => $solicitud->id,
                'usuario_id' => auth()->id(),
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'En revisión',
                'observacion' => $request->observacion ?? 'Movida a revisión',
                'fecha_cambio' => now(),
            ]);
            
            // Crear notificación para el estudiante
            Notificacion::create([
                'usuario_destino_id' => $solicitud->student->user_id,
                'solicitud_id' => $solicitud->id,
                'mensaje' => 'Su solicitud ha sido movida a estado "En revisión".',
                'fecha_envio' => now(),
                'leida' => false,
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Solicitud movida a revisión exitosamente.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al mover la solicitud a revisión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Acciones masivas sobre solicitudes
     */
    public function accionesMasivas(Request $request)
    {
        $request->validate([
            'accion' => 'required|in:aprobar_masivo,rechazar_masivo,revision_masivo',
            'solicitudes_ids' => 'required|string',
        ]);

        $ids = explode(',', $request->solicitudes_ids);
        $ids = array_filter($ids, 'is_numeric');
        
        if (empty($ids)) {
            return redirect()->route('pendientes.index')
                ->with('error', 'No se seleccionaron solicitudes válidas.');
        }

        $solicitudes = Solicitud::with('estadoActual')->whereIn('id', $ids)->get();
        
        if ($solicitudes->isEmpty()) {
            return redirect()->route('pendientes.index')
                ->with('error', 'No se encontraron las solicitudes seleccionadas.');
        }

        DB::beginTransaction();
        try {
            $count = 0;
            $errors = [];
            
            foreach ($solicitudes as $solicitud) {
                try {
                    switch ($request->accion) {
                        case 'aprobar_masivo':
                            $this->aprobarMasivaIndividual($solicitud);
                            break;
                        case 'rechazar_masivo':
                            $this->rechazarMasivaIndividual($solicitud, $request->motivo ?? 'Rechazo masivo');
                            break;
                        case 'revision_masivo':
                            $this->moverRevisionMasivaIndividual($solicitud);
                            break;
                    }
                    $count++;
                } catch (\Exception $e) {
                    $errors[] = "Solicitud #{$solicitud->id}: " . $e->getMessage();
                }
            }
            
            DB::commit();
            
            $message = "{$count} solicitudes procesadas exitosamente.";
            if (!empty($errors)) {
                $message .= " Errores: " . implode('; ', $errors);
                return redirect()->route('pendientes.index')
                    ->with('warning', $message);
            }
            
            return redirect()->route('pendientes.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('pendientes.index')
                ->with('error', 'Error al procesar las solicitudes: ' . $e->getMessage());
        }
    }

    /**
     * Métodos auxiliares para acciones masivas
     */
    private function aprobarMasivaIndividual(Solicitud $solicitud)
    {
        $estadosPermitidos = ['Pendiente', 'En revisión'];
        if (!in_array($solicitud->estadoActual->nombre, $estadosPermitidos)) {
            throw new \Exception('No está en estado pendiente o en revisión');
        }

        $estadoAprobada = Estado::where('nombre', 'Aprobada')->firstOrFail();
        
        $estadoAnterior = $solicitud->estadoActual->nombre;
        $solicitud->update(['estado_actual_id' => $estadoAprobada->id]);
        
        HistorialEstado::create([
            'solicitud_id' => $solicitud->id,
            'usuario_id' => auth()->id(),
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => 'Aprobada',
            'observacion' => 'Aprobación masiva',
            'fecha_cambio' => now(),
        ]);
        
        Notificacion::create([
            'usuario_destino_id' => $solicitud->student->user_id,
            'solicitud_id' => $solicitud->id,
            'mensaje' => 'Su solicitud ha sido aprobada (procesamiento masivo).',
            'fecha_envio' => now(),
            'leida' => false,
        ]);
    }

    private function rechazarMasivaIndividual(Solicitud $solicitud, $motivo)
    {
        $estadosPermitidos = ['Pendiente', 'En revisión'];
        if (!in_array($solicitud->estadoActual->nombre, $estadosPermitidos)) {
            throw new \Exception('No está en estado pendiente o en revisión');
        }

        $estadoRechazada = Estado::where('nombre', 'Rechazada')->firstOrFail();
        
        $estadoAnterior = $solicitud->estadoActual->nombre;
        $solicitud->update([
            'estado_actual_id' => $estadoRechazada->id,
            'observaciones_secretaria' => $motivo,
        ]);
        
        HistorialEstado::create([
            'solicitud_id' => $solicitud->id,
            'usuario_id' => auth()->id(),
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => 'Rechazada',
            'observacion' => $motivo,
            'fecha_cambio' => now(),
        ]);
        
        Notificacion::create([
            'usuario_destino_id' => $solicitud->student->user_id,
            'solicitud_id' => $solicitud->id,
            'mensaje' => 'Su solicitud ha sido rechazada. Motivo: ' . $motivo,
            'fecha_envio' => now(),
            'leida' => false,
        ]);
    }

    private function moverRevisionMasivaIndividual(Solicitud $solicitud)
    {
        if ($solicitud->estadoActual->nombre !== 'Pendiente') {
            throw new \Exception('No está en estado pendiente');
        }

        $estadoRevision = Estado::where('nombre', 'En revisión')->firstOrFail();
        
        $estadoAnterior = $solicitud->estadoActual->nombre;
        $solicitud->update(['estado_actual_id' => $estadoRevision->id]);
        
        HistorialEstado::create([
            'solicitud_id' => $solicitud->id,
            'usuario_id' => auth()->id(),
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => 'En revisión',
            'observacion' => 'Movida a revisión (procesamiento masivo)',
            'fecha_cambio' => now(),
        ]);
        
        Notificacion::create([
            'usuario_destino_id' => $solicitud->student->user_id,
            'solicitud_id' => $solicitud->id,
            'mensaje' => 'Su solicitud ha sido movida a estado "En revisión" (procesamiento masivo).',
            'fecha_envio' => now(),
            'leida' => false,
        ]);
    }

    /**
     * Obtener estadísticas detalladas de pendientes
     */
    public function estadisticas()
    {
        $estadoPendiente = Estado::where('nombre', 'Pendiente')->first();
        $estadoRevision = Estado::where('nombre', 'En revisión')->first();
        
        if (!$estadoPendiente || !$estadoRevision) {
            return redirect()->route('dashboard')
                ->with('error', 'Estados necesarios no configurados.');
        }
        
        // Solicitudes por tipo en estado pendiente
        $solicitudesPorTipo = TipoSolicitud::withCount([
            'solicitudes' => function($query) use ($estadoPendiente, $estadoRevision) {
                $query->whereIn('estado_actual_id', [$estadoPendiente->id, $estadoRevision->id]);
            }
        ])->get();
        
        // Solicitudes pendientes por día (últimos 7 días)
        $solicitudesUltimos7Dias = Solicitud::selectRaw('DATE(created_at) as fecha, COUNT(*) as total')
            ->whereIn('estado_actual_id', [$estadoPendiente->id, $estadoRevision->id])
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();
        
        // Tiempo promedio en pendiente
        $tiempoPromedio = Solicitud::whereIn('estado_actual_id', [$estadoPendiente->id, $estadoRevision->id])
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, NOW())) as horas_promedio')
            ->first();
        
        // Solicitudes más antiguas
        $solicitudesAntiguas = Solicitud::with(['student.user', 'tipoSolicitud'])
            ->whereIn('estado_actual_id', [$estadoPendiente->id, $estadoRevision->id])
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();
        
        return view('pendientes.estadisticas', compact(
            'solicitudesPorTipo',
            'solicitudesUltimos7Dias',
            'tiempoPromedio',
            'solicitudesAntiguas'
        ));
    }
}