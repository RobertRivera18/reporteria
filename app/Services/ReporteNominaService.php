<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReporteNominaService
{
    public function obtenerNominaProcesada(array $filtros = [])
    {
        $fechaInicio = $filtros['fecha_inicio'] ?? '2026-07-01';
        $fechaFin    = $filtros['fecha_fin'] ?? '2026-07-30';
        $idCompania  = $filtros['id_compania'] ?? 5917;

        $query = DB::connection('sqlsrv_vpn')
            ->table('VN_PERSONAEMPLEADO as p')
            ->join('VN_DETALLELIQUIDACION as d', 'd.IdEmpleado', '=', 'p.IdEmpleado')
            ->join('VN_SOLICITUDLIQUIDACION as s', 's.IdSolicitudLiquidacion', '=', 'd.IdSolicitudLiquidacion')
            ->leftJoin('VN_COMPANIA as c', 'c.IdCompania', '=', 'p.IdCompania')
            ->select(
                'p.IdEmpleado',
                'p.NumeroDocumento',
                'p.NombreCompleto',
                'p.FondoReserva',
                'p.NominaCompania',
                'c.NombreCompania',
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
            ->where('p.IdCompania', $idCompania)
            ->whereDate('s.FechaInicioPeriodo', $fechaInicio)
            ->whereDate('s.FechaFinPeriodo', $fechaFin);

        if (!empty($filtros['nomina'])) {
            $query->where('p.NominaCompania', $filtros['nomina']);
        }

        if (!empty($filtros['id_empleado'])) {
            $query->where('p.IdEmpleado', $filtros['id_empleado']);
        }

        $filas = $query->get();

        if ($filas->isEmpty()) {
            return [
                'nombreCompania' => '',
                'bloquesReporte' => [],
            ];
        }

        $nombreCompania = $filas->first()->NombreCompania ?? '';

        // Agrupar por tipo de Nómina y transformar a un array de bloques
        $bloquesReporte = $filas->groupBy('NominaCompania')->map(function ($filasNomina, $nombreNomina) {

            // Conceptos únicos de ingresos
            $conceptosIngresos = $filasNomina
                ->filter(fn($f) => in_array(strtolower(trim($f->Naturaleza)), ['pago', 'ingreso']))
                ->pluck('Concepto')
                ->unique()
                ->values()
                ->all();

            // Conceptos únicos de descuentos
            $conceptosDescuentos = $filasNomina
                ->filter(fn($f) => strtolower(trim($f->Naturaleza)) === 'descuento')
                ->reject(fn($f) => stripos($f->Concepto, 'FONDOS DE RESERVA') !== false)
                ->pluck('Concepto')
                ->unique()
                ->values()
                ->all();

            // Procesar empleados dentro de la nómina
            $empleados = $filasNomina->groupBy('IdEmpleado')->map(function ($grupo) {
                $primero = $grupo->first();
                $esFondoAcumulado = (strtoupper(trim($primero->FondoReserva)) === 'Y');

                $ingresos = $grupo->filter(fn($f) => in_array(strtolower(trim($f->Naturaleza)), ['pago', 'ingreso']));
                $descuentos = $grupo->filter(fn($f) => strtolower(trim($f->Naturaleza)) === 'descuento')
                    ->reject(fn($f) => stripos($f->Concepto, 'FONDOS DE RESERVA') !== false);

                $ingresosDetalle = $ingresos->mapWithKeys(function ($item) use ($esFondoAcumulado) {
                    $esFondoReserva = stripos($item->Concepto, 'FONDO DE RESERVA') !== false;

                    if ($esFondoAcumulado && $esFondoReserva) {
                        return [$item->Concepto => 0.00];
                    }

                    return [$item->Concepto => (float) $item->ValorConcepto];
                });

                $descuentosDetalle = $descuentos->pluck('ValorConcepto', 'Concepto');

                return (object) [
                    'IdEmpleado'        => $primero->IdEmpleado,
                    'NumeroDocumento'   => $primero->NumeroDocumento,
                    'NombreCompleto'    => $primero->NombreCompleto,
                    'FondoReserva'      => $primero->FondoReserva,
                    'Dias'              => $primero->DiasTrabajados,
                    'IngresosDetalle'   => $ingresosDetalle,
                    'TotalIngresos'     => $ingresosDetalle->sum(),
                    'DescuentosDetalle' => $descuentosDetalle,
                    'TotalDescuentos'   => (float) ($primero->TotalDescuentoEmpleado ?? $descuentos->sum('ValorConcepto')),
                    'NetoARecibir'      => (float) $primero->NetoPagoEmpleado
                ];
            })->values();

            return [
                'nombreNomina'         => $nombreNomina,
                'cantidadTrabajadores' => $empleados->count(),
                'conceptosIngresos'    => $conceptosIngresos,
                'conceptosDescuentos'  => $conceptosDescuentos,
                'empleados'            => $empleados,
            ];
        })->values()->all();

        return [
            'nombreCompania' => $nombreCompania,
            'bloquesReporte' => $bloquesReporte,
        ];
    }
}