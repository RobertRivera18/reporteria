<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReporteNominaService
{
    private const NAT_INGRESO   = ['pago', 'ingreso'];
    private const NAT_DESCUENTO = 'descuento';

    private const NOMINA_CON_AREAS = 'NOMINA CLARO';

    private const AREAS_CLARO = [
        'OPERATIVO GYE',
        'OPERATIVO UIO',
        'ADMINISTRATIVO GYE',
        'ADMINISTRATIVO UIO',
    ];

    public function obtenerNominaProcesada(array $filtros = [])
    {
        $fechaInicio = $filtros['fecha_inicio'] ?? '2026-07-01';
        $fechaFin    = $filtros['fecha_fin'] ?? '2026-07-30';
        $idCompania = array_key_exists('id_compania', $filtros) ? $filtros['id_compania'] : 5917;

        $iniExcl = Carbon::parse($fechaInicio)->addDay()->toDateString();
        $finExcl = Carbon::parse($fechaFin)->addDay()->toDateString();

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
                'p.AreaFuncional',
                'c.NombreCompania',
                's.IdSolicitudLiquidacion',
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
            ->where('s.FechaInicioPeriodo', '>=', $fechaInicio)
            ->where('s.FechaInicioPeriodo', '<', $iniExcl)
            ->where('s.FechaFinPeriodo', '>=', $fechaFin)
            ->where('s.FechaFinPeriodo', '<', $finExcl);


        if (!empty($idCompania)) {
            $query->where('p.IdCompania', $idCompania);
        }
        if (!empty($filtros['nomina'])) {
            $query->where('p.NominaCompania', $filtros['nomina']);
        }

        $query->where(function ($q) {
            $q->whereNull('p.NominaCompania')
              ->orWhere('p.NominaCompania', '<>', self::NOMINA_CON_AREAS)
              ->orWhereIn(DB::raw('UPPER(LTRIM(RTRIM(p.AreaFuncional)))'), self::AREAS_CLARO);
        });

        if (!empty($filtros['area_funcional'])) {
            $query->where('p.AreaFuncional', $filtros['area_funcional']);
        }
        if (!empty($filtros['id_empleado'])) {
            $query->where('p.IdEmpleado', $filtros['id_empleado']);
        }
        $filas = $query
            ->orderBy('c.NombreCompania')
            ->orderBy('p.NominaCompania')
            ->orderBy('p.AreaFuncional')
            ->orderBy('p.IdEmpleado')
            ->orderBy('s.IdSolicitudLiquidacion')
            ->orderBy('d.IdConcepto')
            ->get();

        if ($filas->isEmpty()) {
            return ['nombreCompania' => '', 'bloquesReporte' => []];
        }
        $bloquesReporte = $filas
            ->groupBy(function ($f) {
                $partes = [$f->NombreCompania ?? '', $f->NominaCompania ?? ''];
                if ($this->esNominaConAreas($f->NominaCompania)) {
                    $partes[] = strtoupper(trim($f->AreaFuncional ?? ''));
                }
                return implode('|', $partes);
            })
            ->map(fn ($filasBloque) => $this->armarBloque($filasBloque))
            ->values()
            ->all();

        return [
            'nombreCompania' => empty($idCompania)
                ? 'Todas las compañías'
                : ($filas->first()->NombreCompania ?? ''),
            'bloquesReporte' => $bloquesReporte,
        ];
    }

    private function armarBloque(Collection $filasBloque): array
    {
        $esIngreso = fn ($f) => in_array(strtolower(trim($f->Naturaleza)), self::NAT_INGRESO);
        $esDescuento = fn ($f) => strtolower(trim($f->Naturaleza)) === self::NAT_DESCUENTO
            && stripos($f->Concepto, 'FONDOS DE RESERVA') === false;
        $solicitudesPorEmpleado = $filasBloque
            ->groupBy('IdEmpleado')
            ->map(fn ($g) => $g->pluck('IdSolicitudLiquidacion')->unique()->count());

        $filasElegidas = $filasBloque
            ->groupBy('IdEmpleado')
            ->flatMap(fn ($grupo) => $grupo->where(
                'IdSolicitudLiquidacion',
                $this->elegirSolicitud($grupo)
            ))
            ->values();

        $empleados = $filasElegidas
            ->groupBy('IdEmpleado')
            ->map(function ($grupo, $idEmpleado) use ($esIngreso, $esDescuento, $solicitudesPorEmpleado) {
                $primero          = $grupo->first();
                $esFondoAcumulado = strtoupper(trim($primero->FondoReserva)) === 'Y';
                $ingresosDetalle = $grupo->filter($esIngreso)
                    ->groupBy('Concepto')
                    ->map(function ($items, $concepto) use ($esFondoAcumulado) {
                        if ($esFondoAcumulado && stripos($concepto, 'FONDO DE RESERVA') !== false) {
                            return 0.0;
                        }
                        return (float) $items->sum('ValorConcepto');
                    });

                $descuentosDetalle = $grupo->filter($esDescuento)
                    ->groupBy('Concepto')
                    ->map(fn ($items) => (float) $items->sum('ValorConcepto'));

                $descuentoDeclarado = $grupo->max('TotalDescuentoEmpleado');

                return (object) [
                    'IdEmpleado'        => $primero->IdEmpleado,
                    'NumeroDocumento'   => $primero->NumeroDocumento,
                    'NombreCompleto'    => $primero->NombreCompleto,
                    'FondoReserva'      => $primero->FondoReserva,
                    'Dias'              => $grupo->max('DiasTrabajados'),
                    'IdSolicitud'       => $primero->IdSolicitudLiquidacion,
                    'Solicitudes'       => $solicitudesPorEmpleado[$idEmpleado] ?? 1, // >1 = hubo otras ignoradas
                    'IngresosDetalle'   => $ingresosDetalle,
                    'TotalIngresos'     => $ingresosDetalle->sum(),
                    'DescuentosDetalle' => $descuentosDetalle,
                    'TotalDescuentos'   => (float) ($descuentoDeclarado ?? $descuentosDetalle->sum()),
                    'NetoARecibir'      => (float) $grupo->max('NetoPagoEmpleado'),
                ];
            })
            ->values();

        $primera = $filasBloque->first();
        $area = $this->esNominaConAreas($primera->NominaCompania)
            ? strtoupper(trim($primera->AreaFuncional ?? ''))
            : null;

        return [
            'nombreCompania'       => $primera->NombreCompania ?? '',
            'nombreNomina'         => $primera->NominaCompania,
            'areaFuncional'        => $area,
            'subgrupo'             => $area,
            'cantidadTrabajadores' => $empleados->count(),
            'conceptosIngresos'    => $filasElegidas->filter($esIngreso)->pluck('Concepto')->unique()->values()->all(),
            'conceptosDescuentos'  => $filasElegidas->filter($esDescuento)->pluck('Concepto')->unique()->values()->all(),
            'empleados'            => $empleados,
        ];
    }

    private function esNominaConAreas(?string $nomina): bool
    {
        return strtoupper(trim($nomina ?? '')) === self::NOMINA_CON_AREAS;
    }


    private function elegirSolicitud(Collection $grupoEmpleado)
    {
        return $grupoEmpleado
            ->groupBy('IdSolicitudLiquidacion')
            ->map(fn ($g, $id) => [
                'id'   => (int) $id,
                'neto' => (float) $g->max('NetoPagoEmpleado'),
            ])
            ->sort(fn ($a, $b) => [$b['neto'], $b['id']] <=> [$a['neto'], $a['id']])
            ->first()['id'];
    }
}
