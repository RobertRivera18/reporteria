<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NominaController;

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

Route::get('/nomina', [NominaController::class, 'index'])->name('nomina.index');
Route::get('/nomina/pdf', [NominaController::class, 'exportarPdf'])->name('nomina.pdf');
