<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\TipoSolicitud;
use App\Models\Estado;
use App\Models\HistorialEstado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PendienteController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function index(Request $request)
    {
        $query = Solicitud::with(['student.user', 'tipoSolicitud', 'estadoActual'])
            ->whereHas('estadoActual', function($q) {
                $q->whereIn('nombre', ['Pendiente', 'En revisión']);
            });

        if ($request->filled('tipo_solicitud_id')) {
            $query->where('tipo_solicitud_id', $request->tipo_solicitud_id);
        }

        $solicitudes = $query->latest()->paginate(10);

        $totalPendientes = Solicitud::whereHas('estadoActual', fn($q) => 
            $q->where('nombre', 'Pendiente'))->count();
            
        $totalEnRevision = Solicitud::whereHas('estadoActual', fn($q) => 
            $q->where('nombre', 'En revisión'))->count();
            
        $tiposSolicitud = TipoSolicitud::all();

        return view('pendientes.index', compact(
            'solicitudes', 
            'totalPendientes', 
            'totalEnRevision', 
            'tiposSolicitud'
        ));
    }

    // Botón Check: Pendiente -> En Revisión
    public function updateEstado(Solicitud $solicitud)
    {
        $estadoPendiente = Estado::where('nombre', 'Pendiente')->first();
        $estadoRevision = Estado::where('nombre', 'En revisión')->first();

        if (!$estadoPendiente || !$estadoRevision) {
            return back()->with('error', 'Configuración de estados incompleta.');
        }

        if ($solicitud->estado_actual_id != $estadoPendiente->id) {
            return back()->with('info', 'La solicitud ya está siendo procesada.');
        }

        try {
            DB::transaction(function () use ($solicitud, $estadoPendiente, $estadoRevision) {
                HistorialEstado::create([
                    'solicitud_id' => $solicitud->id,
                    'usuario_id' => auth()->id(),
                    'estado_anterior' => $estadoPendiente->nombre,
                    'estado_nuevo' => $estadoRevision->nombre,
                    'estado_id' => $estadoRevision->id,
                    'fecha_cambio' => now(),
                    'observacion' => 'Revisión iniciada desde panel de pendientes',
                ]);

                $solicitud->update(['estado_actual_id' => $estadoRevision->id]);
            });

            return back()->with('success', 'Solicitud marcada en revisión.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // NUEVO: Botón Rechazar directo desde pendientes
    public function rechazar(Solicitud $solicitud)
    {
        $estadoRechazado = Estado::where('nombre', 'Rechazado')->first();
        
        if (!$estadoRechazado) {
            return back()->with('error', 'Estado Rechazado no encontrado en el sistema.');
        }

        // Solo se puede rechazar si está Pendiente (opcional, quita esto si quieres rechazar también en revisión)
        if ($solicitud->estadoActual->nombre !== 'Pendiente') {
            return back()->with('error', 'Solo se pueden rechazar solicitudes Pendientes.');
        }

        try {
            DB::transaction(function () use ($solicitud, $estadoRechazado) {
                $estadoAnterior = $solicitud->estadoActual;

                HistorialEstado::create([
                    'solicitud_id' => $solicitud->id,
                    'usuario_id' => auth()->id(),
                    'estado_anterior' => $estadoAnterior ? $estadoAnterior->nombre : null,
                    'estado_nuevo' => $estadoRechazado->nombre,
                    'estado_id' => $estadoRechazado->id,
                    'fecha_cambio' => now(),
                    'observacion' => 'Rechazado rápidamente desde panel de pendientes',
                ]);

                $solicitud->update(['estado_actual_id' => $estadoRechazado->id]);
            });

            return back()->with('success', 'Solicitud rechazada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al rechazar: ' . $e->getMessage());
        }
    }
}