<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\ReporteNominaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class ReporteNomina extends Component
{
    public $fecha_inicio = '2026-07-01';
    public $fecha_fin    = '2026-07-30';
    public $id_compania  = 5915;
    public $nomina       = '';
    public $id_empleado  = '';

    public $companias = [];
    public $nominas   = [];

    public function mount()
    {
        $this->companias = DB::connection('sqlsrv_vpn')
            ->table('VN_COMPANIA')
            ->select('IdCompania', 'NombreCompania')
            ->get();

        $this->cargarNominas();
    }

    public function updatedIdCompania()
    {
        $this->nomina = '';
        $this->cargarNominas();
    }

    public function cargarNominas()
    {
        if ($this->id_compania) {
            $this->nominas = DB::connection('sqlsrv_vpn')
                ->table('VN_PERSONAEMPLEADO')
                ->where('IdCompania', $this->id_compania)
                ->where('EstadoEmpleado', 'ACTIVO')
                ->whereNotNull('NominaCompania')
                ->distinct()
                ->pluck('NominaCompania');
        } else {
            $this->nominas = [];
        }
    }

    public function exportarPdf(ReporteNominaService $service)
    {
        $filtros = [
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin'    => $this->fecha_fin,
            'id_compania'  => $this->id_compania,
            'nomina'       => $this->nomina,
            'id_empleado'  => $this->id_empleado,
        ];

        $data = $service->obtenerNominaProcesada($filtros);
        $pdf = Pdf::loadView('nomina.pdf', $data + ['filtros' => $filtros])
            ->setPaper('a4', 'landscape')                                  // el original es A3 horizontal
            ->setOption(['isPhpEnabled' => true, 'enable_php' => true]);   // para "Page X of Y"

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'reporte_nomina.pdf'
        );
    }

    public function render(ReporteNominaService $service)
    {
        $filtros = [
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin'    => $this->fecha_fin,
            'id_compania'  => $this->id_compania,
            'nomina'       => $this->nomina,
            'id_empleado'  => $this->id_empleado,
        ];

        $data = $service->obtenerNominaProcesada($filtros);

        return view('livewire.reporte-nomina', [
            'nombreCompania' => $data['nombreCompania'] ?? '',
            'bloquesReporte' => $data['bloquesReporte'] ?? [],
        ]);
    }
}
