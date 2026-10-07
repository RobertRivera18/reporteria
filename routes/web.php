<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NominaController;
use App\Services\ReporteNominaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/empleados-compania', function () {
    try {
        $filas = DB::connection('sqlsrv_vpn')
            ->table('VN_PERSONAEMPLEADO as p')
            ->join('VN_DETALLELIQUIDACION as d', 'd.IdEmpleado', '=', 'p.IdEmpleado')
            ->join('VN_SOLICITUDLIQUIDACION as s', 's.IdSolicitudLiquidacion', '=', 'd.IdSolicitudLiquidacion')
            ->select(
                'p.IdEmpleado',
                'p.NumeroDocumento',
                'p.NombreCompleto',
                'p.FondoReserva', // <--- Se agrega la columna FondoReserva
                'd.IdConcepto',
                'd.Concepto',
                'd.Naturaleza',
                'd.DiasTrabajados',
                'd.DiasAusentismos',
                'd.TotalPagoEmpleado',
                'd.TotalDescuentoEmpleado',
                'd.NetoPagoEmpleado'
            )
            ->selectRaw('CAST(ROUND(d.ValorConcepto, 2) AS DECIMAL(18,2)) AS ValorConcepto')
            ->where('p.NominaCompania', 'NOMINA ADM')
            ->where('p.EstadoEmpleado', 'ACTIVO')
            ->where('p.IdCompania', 5915)
            //->where('p.IdEmpleado', 2597)
            ->whereDate('s.FechaInicioPeriodo', '2026-07-01')
            ->whereDate('s.FechaFinPeriodo', '2026-07-30')
            ->get();

        $empleados = $filas->groupBy('IdEmpleado')->map(function ($grupo) {
            $primero = $grupo->first();

            $ingresos = $grupo->filter(function ($f) {
                return strtolower(trim($f->Naturaleza)) === 'pago' || strtolower(trim($f->Naturaleza)) === 'ingreso';
            });

            $descuentos = $grupo->filter(function ($f) {
                return strtolower(trim($f->Naturaleza)) === 'descuento';
            });

            $ingresosDetalle = $ingresos->pluck('ValorConcepto', 'Concepto');
            $descuentosDetalle = $descuentos->pluck('ValorConcepto', 'Concepto');

            $ingresosParaSumar = $ingresos;
            if (strtoupper(trim($primero->FondoReserva)) === 'Y') {
                $ingresosParaSumar = $ingresos->reject(function ($item) {
                    return stripos($item->Concepto, 'FONDO DE RESERVA') !== false;
                });
            }

            $totalIngresos = (float) $ingresosParaSumar->sum('ValorConcepto');
            $totalDescuentos = (float) ($primero->TotalDescuentoEmpleado ?? $descuentos->sum('ValorConcepto'));

            return [
                'IdEmpleado'      => $primero->IdEmpleado,
                'NumeroDocumento' => $primero->NumeroDocumento,
                'NombreCompleto'  => $primero->NombreCompleto,
                'Dias'            => $primero->DiasTrabajados,
                'Ingresos' => [
                    'detalles' => $ingresosDetalle,
                    'Total'    => $totalIngresos
                ],
                'Descuentos' => [
                    'detalles' => $descuentosDetalle,
                    'Total'    => $totalDescuentos
                ],
                'NetoARecibir'    => (float) $primero->NetoPagoEmpleado
            ];
        })->values();

        return response()->json($empleados, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        report($e);

        return response()->json([
            'error' => 'No se pudo consultar la base. Verifica la VPN o el nombre de la columna.',
        ], 500);
    }
});


Route::get('/nominas-compania', function () {
    try {
        $nominas = DB::connection('sqlsrv_vpn')
            ->table('VN_PERSONAEMPLEADO')
            ->select('NominaCompania')
            ->distinct()
            ->where('EstadoEmpleado', 'ACTIVO')
            ->whereNotNull('NominaCompania')
            ->get()
            ->pluck('NominaCompania')
            ->map(fn($item) => trim($item))
            ->unique()
            ->values();

        return response()->json($nominas, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        report($e);

        return response()->json([
            'error'   => 'No se pudo consultar VN_PERSONAEMPLEADO. Verifica la conexión VPN.',
            'details' => $e->getMessage()
        ], 500);
    }
});

Route::get('/nomina', [NominaController::class, 'index'])->name('nomina.index');
Route::get('/nomina/pdf', [NominaController::class, 'exportarPdf'])->name('nomina.pdf');
Route::get('/nomina/dashboard', [NominaController::class, 'dashboard'])->name('nomina.dashboard');

Route::get('/debug/nomina/agrupadores', function (Request $request) {
    abort_if(app()->isProduction(), 404);

    $compania = $request->query(5916); // id de la compañía de Claro

    $filas = DB::connection('sqlsrv_vpn')
        ->table('VN_PERSONAEMPLEADO as p')
        ->leftJoin('VN_COMPANIA as c', 'c.IdCompania', '=', 'p.IdCompania')
        ->when($compania, fn ($q) => $q->where('p.IdCompania', $compania))
        ->when(!$compania, fn ($q) => $q->where('c.NombreCompania', 'like', '%CLARO%'))
        ->groupBy(
            'c.NombreCompania', 'p.NominaCompania', 'p.AreaFuncional',
            'p.ValorAgrupador1', 'p.ValorAgrupador2', 'p.ValorAgrupador3',
            'p.ValorAgrupador4', 'p.ValorAgrupador5', 'p.ValorAgrupador6'
        )
        ->selectRaw('c.NombreCompania, p.NominaCompania, p.AreaFuncional,
            p.ValorAgrupador1, p.ValorAgrupador2, p.ValorAgrupador3,
            p.ValorAgrupador4, p.ValorAgrupador5, p.ValorAgrupador6,
            COUNT(*) AS empleados')
        ->orderBy('p.AreaFuncional')
        ->get();

    return response()->json($filas, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

Route::get('/debug/nomina', function (Request $request, ReporteNominaService $servicio) {
    // Solo en entornos no productivos
    abort_if(app()->isProduction(), 404);

    $inicio   = $request->query('inicio', '2026-07-01');
    $fin      = $request->query('fin', '2026-07-30');
    $compania = $request->query('compania');   // opcional: ?compania=5917

    $fechaFinExcl = Carbon::parse($fin)->addDay()->toDateString();
    $fechaIniExcl = Carbon::parse($inicio)->addDay()->toDateString();

    // 1) Empleados con más de un neto distinto o más de una solicitud en el período
    $duplicados = DB::connection('sqlsrv_vpn')
        ->table('VN_PERSONAEMPLEADO as p')
        ->join('VN_DETALLELIQUIDACION as d', 'd.IdEmpleado', '=', 'p.IdEmpleado')
        ->join('VN_SOLICITUDLIQUIDACION as s', 's.IdSolicitudLiquidacion', '=', 'd.IdSolicitudLiquidacion')
        ->where('s.FechaInicioPeriodo', '>=', $inicio)
        ->where('s.FechaInicioPeriodo', '<', $fechaIniExcl)
        ->where('s.FechaFinPeriodo', '>=', $fin)
        ->where('s.FechaFinPeriodo', '<', $fechaFinExcl)
        ->when($compania, fn ($q) => $q->where('p.IdCompania', $compania))
        ->groupBy('p.IdCompania', 'p.IdEmpleado', 'p.NombreCompleto')
        ->havingRaw('COUNT(DISTINCT d.NetoPagoEmpleado) > 1 OR COUNT(DISTINCT s.IdSolicitudLiquidacion) > 1')
        ->selectRaw('p.IdCompania, p.IdEmpleado, p.NombreCompleto,
                     COUNT(DISTINCT d.NetoPagoEmpleado) AS netos_distintos,
                     COUNT(DISTINCT s.IdSolicitudLiquidacion) AS solicitudes')
        ->limit(100)
        ->get();

    // 2) Neto por compañía usando el servicio actual, con y sin filtro de compañía
    $resumir = function (array $resultado) {
        return collect($resultado['bloquesReporte'])
            ->groupBy('nombreCompania')
            ->map(fn ($bloques) => [
                'personas'   => $bloques->sum('cantidadTrabajadores'),
                'neto'       => round($bloques->flatMap(fn ($b) => $b['empleados'])->sum('NetoARecibir'), 2),
                'ingresos'   => round($bloques->flatMap(fn ($b) => $b['empleados'])->sum('TotalIngresos'), 2),
                'descuentos' => round($bloques->flatMap(fn ($b) => $b['empleados'])->sum('TotalDescuentos'), 2),
            ]);
    };

    $base = ['fecha_inicio' => $inicio, 'fecha_fin' => $fin];

    $sinFiltro = $resumir($servicio->obtenerNominaProcesada($base + ['id_compania' => '']));

    $comparacion = $sinFiltro->map(function ($datos, $nombre) use ($servicio, $resumir, $base) {
        // Buscar el IdCompania por nombre para repetir la consulta filtrada
        $id = DB::connection('sqlsrv_vpn')->table('VN_COMPANIA')
            ->where('NombreCompania', $nombre)->value('IdCompania');

        $conFiltro = $id
            ? $resumir($servicio->obtenerNominaProcesada($base + ['id_compania' => $id]))->get($nombre)
            : null;

        return [
            'sin_filtro'      => $datos,
            'con_filtro'      => $conFiltro,
            'diferencia_neto' => $conFiltro ? round($conFiltro['neto'] - $datos['neto'], 2) : null,
        ];
    });

    return response()->json([
        'parametros'               => compact('inicio', 'fin', 'compania'),
        'empleados_conflicto'      => $duplicados,
        'comparacion_por_compania' => $comparacion,
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
})->name('debug.nomina');
