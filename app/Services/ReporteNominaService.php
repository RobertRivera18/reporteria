<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReporteNominaService
{
    public function obtenerNominaProcesada(array $filtros = [])
    {
        $fechaInicio = $filtros['fecha_inicio'] ?? '2026-07-01';
        $fechaFin    = $filtros['fecha_fin'] ?? '2026-07-30';

        // Si la clave viene vacía ('' o null) se consideran TODAS las compañías.
        // Solo se usa el valor por defecto cuando la clave no existe.
        $idCompania = array_key_exists('id_compania', $filtros) ? $filtros['id_compania'] : 5917;

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
            ->whereDate('s.FechaInicioPeriodo', $fechaInicio)
            ->whereDate('s.FechaFinPeriodo', $fechaFin);

        // 👇 Cambio: el filtro de compañía ahora es opcional
        if (!empty($idCompania)) {
            $query->where('p.IdCompania', $idCompania);
        } else {
            $query->orderBy('c.NombreCompania');
        }

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

        // 👇 Cambio: se agrupa por compañía + nómina, para que dos compañías
        //    con una nómina del mismo nombre no se mezclen.
        $bloquesReporte = $filas
            ->groupBy(fn ($f) => ($f->NombreCompania ?? '') . '|' . $f->NominaCompania)
            ->map(function ($filasNomina) {

                $nombreNomina        = $filasNomina->first()->NominaCompania;
                $nombreCompaniaBloque = $filasNomina->first()->NombreCompania ?? '';

                // Conceptos únicos de ingresos
                $conceptosIngresos = $filasNomina
                    ->filter(fn ($f) => in_array(strtolower(trim($f->Naturaleza)), ['pago', 'ingreso']))
                    ->pluck('Concepto')
                    ->unique()
                    ->values()
                    ->all();

                // Conceptos únicos de descuentos
                $conceptosDescuentos = $filasNomina
                    ->filter(fn ($f) => strtolower(trim($f->Naturaleza)) === 'descuento')
                    ->reject(fn ($f) => stripos($f->Concepto, 'FONDOS DE RESERVA') !== false)
                    ->pluck('Concepto')
                    ->unique()
                    ->values()
                    ->all();

                // Procesar empleados dentro de la nómina
                $empleados = $filasNomina->groupBy('IdEmpleado')->map(function ($grupo) {
                    $primero = $grupo->first();
                    $esFondoAcumulado = (strtoupper(trim($primero->FondoReserva)) === 'Y');

                    $ingresos = $grupo->filter(fn ($f) => in_array(strtolower(trim($f->Naturaleza)), ['pago', 'ingreso']));
                    $descuentos = $grupo->filter(fn ($f) => strtolower(trim($f->Naturaleza)) === 'descuento')
                        ->reject(fn ($f) => stripos($f->Concepto, 'FONDOS DE RESERVA') !== false);

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
                        'NetoARecibir'      => (float) $primero->NetoPagoEmpleado,
                    ];
                })->values();

                return [
                    'nombreCompania'       => $nombreCompaniaBloque,   // 👈 nuevo
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
