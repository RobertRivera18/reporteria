<?php

namespace App\Livewire;

use App\Services\ReporteNominaService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DashboardNomina extends Component
{
    public $fecha_inicio = '2026-07-01';
    public $fecha_fin    = '2026-07-30';
    public $id_compania  = '';
    public $nomina       = '';

    public array $companias = [];
    public array $nominas   = [];

    public function mount()
    {
        $this->companias = DB::connection('sqlsrv_vpn')
            ->table('VN_COMPANIA')
            ->select('IdCompania', 'NombreCompania')
            ->get()
            ->map(fn($c) => (array) $c)
            ->all();

        $this->cargarNominas();
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['id_compania', 'nomina', 'fecha_inicio', 'fecha_fin']);
    }
    public function updatedIdCompania()
    {
        $this->nomina = '';
        $this->cargarNominas();
    }

    public function cargarNominas()
    {
        $query = DB::connection('sqlsrv_vpn')
            ->table('VN_PERSONAEMPLEADO')
            ->where('EstadoEmpleado', 'ACTIVO')
            ->whereNotNull('NominaCompania');

        // Con "Todas las compañías" se listan las nóminas de todas
        if ($this->id_compania) {
            $query->where('IdCompania', $this->id_compania);
        }

        $this->nominas = $query->distinct()->orderBy('NominaCompania')->pluck('NominaCompania')->all();
    }

    /**
     * Consulta el servicio una sola vez por request y resume los datos
     * para el dashboard (KPIs, por nómina, por rubro y rankings).
     */
    #[Computed]
    public function resumen(): array
    {
        try {
            $data = app(ReporteNominaService::class)->obtenerNominaProcesada([
                'fecha_inicio' => $this->fecha_inicio,
                'fecha_fin'    => $this->fecha_fin,
                'id_compania'  => $this->id_compania,
                'nomina'       => $this->nomina,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return ['error' => 'No se pudo consultar la información. Verifica la conexión VPN.'];
        }

        $bloques = collect($data['bloquesReporte'] ?? []);

        if ($bloques->isEmpty()) {
            return ['vacio' => true, 'compania' => $data['nombreCompania'] ?? ''];
        }

        $porNomina       = [];
        $rubrosIngresos  = [];
        $rubrosDescuento = [];
        $empleados       = [];

        foreach ($bloques as $b) {
            $emps = collect($b['empleados']);

            $porNomina[] = [
                'compania'   => $b['nombreCompania'] ?? '',
                'nombre'     => $this->id_compania
                    ? $b['nombreNomina']
                    : (($b['nombreCompania'] ?? '') . ' / ' . $b['nombreNomina']),
                'personas'   => (int) $b['cantidadTrabajadores'],
                'ingresos'   => (float) $emps->sum('TotalIngresos'),
                'descuentos' => (float) $emps->sum('TotalDescuentos'),
                'neto'       => (float) $emps->sum('NetoARecibir'),
            ];

            foreach ($emps as $e) {
                foreach ($e->IngresosDetalle as $concepto => $valor) {
                    $rubrosIngresos[$concepto] = ($rubrosIngresos[$concepto] ?? 0) + (float) $valor;
                }
                foreach ($e->DescuentosDetalle as $concepto => $valor) {
                    $rubrosDescuento[$concepto] = ($rubrosDescuento[$concepto] ?? 0) + (float) $valor;
                }

                $empleados[] = [
                    'nombre'     => $e->NombreCompleto,
                    'nomina'     => $b['nombreNomina'],
                    'ingresos'   => (float) $e->TotalIngresos,
                    'descuentos' => (float) $e->TotalDescuentos,
                    'neto'       => (float) $e->NetoARecibir,
                ];
            }
        }

        $porNomina = collect($porNomina)->sortByDesc('neto')->values();
        arsort($rubrosIngresos);
        arsort($rubrosDescuento);
        $rubrosIngresos  = array_filter($rubrosIngresos, fn($v) => $v > 0);
        $rubrosDescuento = array_filter($rubrosDescuento, fn($v) => $v > 0);

        $personas   = (int) $porNomina->sum('personas');
        $ingresos   = (float) $porNomina->sum('ingresos');
        $descuentos = (float) $porNomina->sum('descuentos');
        $neto       = (float) $porNomina->sum('neto');

        $empleados = collect($empleados);

        // Consolidado por compañía (útil cuando se eligen "Todas")
        $porCompania = $porNomina->groupBy('compania')->map(fn($g, $c) => [
            'nombre'     => $c,
            'personas'   => (int) $g->sum('personas'),
            'ingresos'   => (float) $g->sum('ingresos'),
            'descuentos' => (float) $g->sum('descuentos'),
            'neto'       => (float) $g->sum('neto'),
        ])->sortByDesc('neto')->values();

        return [
            'compania'        => $this->id_compania ? ($data['nombreCompania'] ?? '') : 'Todas las compañías',
            'porCompania'     => $porCompania,
            'personas'        => $personas,
            'ingresos'        => $ingresos,
            'descuentos'      => $descuentos,
            'neto'            => $neto,
            'promedio'        => $personas ? $neto / $personas : 0,
            'pctDescuento'    => $ingresos ? $descuentos / $ingresos * 100 : 0,
            'porNomina'       => $porNomina,
            'rubrosIngresos'  => $rubrosIngresos,
            'rubrosDescuento' => $rubrosDescuento,
            'topNeto'         => $empleados->sortByDesc('neto')->take(5)->values(),
            'topDescuento'    => $empleados->sortByDesc('descuentos')->take(5)->values(),
        ];
    }

    public function render()
    {
        return view('livewire.dashboard-nomina');
    }
}
