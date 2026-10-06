<?php

namespace App\Http\Controllers;

use App\Services\ReporteNominaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class NominaController extends Controller
{
    /**
     * Muestra la vista principal que contiene el componente Livewire.
     */
    public function index()
    {
        return view('nomina.index');
    }

    /**
     * Permite exportar el PDF mediante una ruta directa si se requiere.
     */
    public function exportarPdf(Request $request, ReporteNominaService $service)
    {
        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'id_compania', 'nomina', 'id_empleado']);

        $data = $service->obtenerNominaProcesada($filtros);

        $pdf = Pdf::loadView('nomina.pdf', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->stream('reporte_nomina.pdf');
    }
}
