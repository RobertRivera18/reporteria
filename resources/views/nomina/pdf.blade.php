<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Rol de Pago ( Previo al Cierre )</title>
    <style>
        /* A4 horizontal (842 x 595 pt). Espacio reservado para encabezado y pie fijos */
        @page { margin: 88pt 20pt 36pt 20pt; }

        body { font-family: 'Times-Roman', 'Times New Roman', serif; font-size: 7pt; color: #000; }

        /* ---------- Encabezado / pie fijos en cada página ---------- */
        #header { position: fixed; top: -80pt; left: 0; right: 0; height: 74pt; }
        #header .empresa { font-size: 12pt; font-weight: bold; font-style: italic; text-transform: uppercase; }
        #header .titulo  { font-size: 12pt; font-weight: bold; margin-top: 8pt; }
        #header .linea   { border-top: 1.4pt solid #000; margin-top: 6pt; }
        #header .liq     { font-size: 7pt; font-weight: bold; margin-top: 6pt; }

        #footer { position: fixed; bottom: -28pt; left: 0; right: 0; height: 20pt; }
        #footer table { width: 100%; border-collapse: collapse; }
        #footer td { font-size: 6pt; font-weight: bold; font-style: italic; border: none; padding: 0; }
        #footer td.der { text-align: right; }
        #footer .linea { border-top: 1.4pt solid #000; width: 60%; margin-bottom: 3pt; }

        /* ---------- Contenido ---------- */
        .tipo-trabajador { font-family: Helvetica, Arial, sans-serif; font-size: 7pt; font-weight: bold; margin: 4pt 0 5pt 0; }
        .tipo-trabajador span { font-family: 'Times-Roman', serif; font-size: 9pt; margin-left: 20pt; }

        .grupo-titulo { font-size: 8.5pt; font-weight: bold; margin: 7pt 0 5pt 0; }

        table.rol { width: 100%; border-collapse: collapse; }
        table.rol thead { display: table-header-group; }
        table.rol tr { page-break-inside: avoid; }

        table.rol th {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 4.8pt; font-weight: bold;
            border: 0.6pt solid #000; background: #fff;
            padding: 1pt 1.5pt; text-align: left; vertical-align: top;
        }
        table.rol th.grupo   { text-align: center; font-size: 5.8pt; }
        table.rol th.vacio   { border: none; }
        table.rol th.neto    { text-align: center; vertical-align: top; font-size: 5.8pt; }

        table.rol td {
            border: 0.6pt solid #000;
            padding: 0.8pt 1.5pt; font-size: 5.6pt; text-align: right;
            white-space: nowrap;
        }
        table.rol td.nombre { font-family: Helvetica, Arial, sans-serif; font-size: 5pt; text-align: left; white-space: nowrap; }
        table.rol td.base   { text-align: left; font-size: 5.4pt; }
        table.rol td.dias   { text-align: right; font-size: 5.4pt; }
        table.rol tr.total td { font-weight: bold; }
        table.rol td.lbl-total { text-align: left; font-weight: bold; }

        table.resumen { border-collapse: collapse; margin-top: 2pt; }
        table.resumen td { border: none; font-size: 7.5pt; font-weight: bold; padding: 1.5pt 0; vertical-align: top; }
        table.resumen td.txt { width: 400pt; }
        table.resumen td.val { padding-left: 4pt; }
        table.resumen td.sangria { padding-left: 36pt; width: 364pt; }

        /* ---------- Total general ---------- */
        table.general { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 10pt; }
        table.general th {
            font-family: Helvetica, Arial, sans-serif; font-size: 4.4pt; font-weight: bold;
            border: 0.6pt solid #000; background: #e6e6e6; text-align: center; padding: 1pt 0.5pt;
            word-wrap: break-word;
        }
        table.general td { border: 0.6pt solid #000; font-size: 4.8pt; font-weight: bold; text-align: right; padding: 1pt 1pt; white-space: nowrap; }
        table.general td.lbl { text-align: left; font-size: 5pt; }
    </style>
</head>
<body>

@php
    // En blanco cuando el valor es 0 (como el reporte original)
    $fmt  = fn ($v) => ((float) $v) == 0 ? '' : number_format((float) $v, 2, '.', ',');
    $fmtT = fn ($v) => number_format((float) $v, 2, '.', ',');
    $fmtD = fn ($v) => '$ ' . number_format((float) $v, 2, '.', ',');

    $bloques = collect($bloquesReporte);

    // Agrupar por "Tipo de Trabajador" (si no existe, todo queda en un solo grupo)
    $grupos = $bloques->groupBy(fn ($b) => $b['tipoTrabajador'] ?? '');

    // Acumuladores para la tabla "Total General Rubros"
    $generalIngresos   = [];
    $generalDescuentos = [];
    $generalTotalIng   = 0;
    $generalTotalDesc  = 0;
    $generalNeto       = 0;
    $generalCantidad   = 0;

    $usuario = auth()->user()->name ?? '';
@endphp

{{-- ====== ENCABEZADO FIJO ====== --}}
<div id="header">
    <div class="empresa">{{ $nombreCompania }}</div>
    <div class="titulo">ROL DE PAGO ( Previo al Cierre )</div>
    <div class="linea"></div>
    <div class="liq">
        Liquidacion
        {{ \Carbon\Carbon::parse($filtros['fecha_inicio'] ?? now())->format('Y/m/d') }}
        -
        {{ \Carbon\Carbon::parse($filtros['fecha_fin'] ?? now())->format('Y/m/d') }}
    </div>
    <div class="linea"></div>
</div>

{{-- ====== PIE FIJO ====== --}}
<div id="footer">
    <div class="linea"></div>
    <table>
        <tr>
            <td>RRHH / Rol de Pago Previo al Cierre</td>
            <td class="der">{{ $usuario }} / {{ now()->format('j/n/Y / H:i:s') }}</td>
        </tr>
    </table>
</div>

{{-- Numeración "Page X of Y" (requiere isPhpEnabled = true) --}}
<script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->getFont('Times-Roman', 'bolditalic');
        $pdf->page_text(500, 24, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 7, [0, 0, 0]);
    }
</script>

@forelse($grupos as $tipo => $bloquesTipo)

    <div @if(!$loop->first) style="page-break-before: always;" @endif>

        @if($tipo !== '')
            <div class="tipo-trabajador">Tipo de Trabajador : <span>{{ $tipo }}</span></div>
        @endif

        @foreach($bloquesTipo as $nomina)
            @php
                $empleados  = collect($nomina['empleados']);
                $ingresos   = $nomina['conceptosIngresos'];
                $descuentos = $nomina['conceptosDescuentos'];

                // La columna de sueldo base solo se muestra si hay datos
                $conBase = $empleados->contains(fn ($e) => !empty($e->SueldoBase));

                $totIng  = (float) $empleados->sum('TotalIngresos');
                $totDesc = (float) $empleados->sum('TotalDescuentos');
                $totNeto = (float) $empleados->sum('NetoARecibir');
            @endphp

            <div class="grupo-titulo">{{ $nombreCompania }} / {{ $nomina['nombreNomina'] }}</div>

            <table class="rol">
                <thead>
                    <tr>
                        <th rowspan="2" class="vacio" style="width: 150pt;"></th>
                        @if($conBase)<th rowspan="2" class="vacio" style="width: 38pt;"></th>@endif
                        <th rowspan="2" class="vacio" style="width: 16pt;"></th>
                        <th colspan="{{ count($ingresos) + 1 }}" class="grupo">INGRESO</th>
                        <th colspan="{{ count($descuentos) + 1 }}" class="grupo">DESCUENTOS</th>
                        <th rowspan="2" class="neto col-neto">NETO A<br>RECIBIR</th>
                    </tr>
                    <tr>
                        @foreach($ingresos as $c)
                            <th>{{ $c }}</th>
                        @endforeach
                        <th style="text-align:center;">TOTAL</th>

                        @foreach($descuentos as $c)
                            <th>{{ $c }}</th>
                        @endforeach
                        <th style="text-align:center;">TOTAL</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($empleados as $emp)
                        <tr>
                            <td class="nombre col-doc">{{ $emp->NumeroDocumento }} {{ $emp->NombreCompleto }}</td>
                            @if($conBase)
                                <td class="base">@if(!empty($emp->SueldoBase)){{ $fmtD($emp->SueldoBase) }}@endif</td>
                            @endif
                            <td class="dias">{{ number_format((float) $emp->Dias, 0) }}</td>

                            @foreach($ingresos as $c)
                                <td>{{ $fmt($emp->IngresosDetalle[$c] ?? 0) }}</td>
                            @endforeach
                            <td>{{ $fmtT($emp->TotalIngresos) }}</td>

                            @foreach($descuentos as $c)
                                <td>{{ $fmt($emp->DescuentosDetalle[$c] ?? 0) }}</td>
                            @endforeach
                            <td>{{ $fmtT($emp->TotalDescuentos) }}</td>

                            <td>{{ $fmtT($emp->NetoARecibir) }}</td>
                        </tr>
                    @endforeach

                    {{-- Fila Total --}}
                    <tr class="total">
                        <td class="lbl-total" colspan="{{ $conBase ? 3 : 2 }}">Total</td>

                        @foreach($ingresos as $c)
                            @php
                                $s = (float) $empleados->sum(fn ($e) => (float) ($e->IngresosDetalle[$c] ?? 0));
                                $generalIngresos[$c] = ($generalIngresos[$c] ?? 0) + $s;
                            @endphp
                            <td>{{ $fmtT($s) }}</td>
                        @endforeach
                        <td>{{ $fmtT($totIng) }}</td>

                        @foreach($descuentos as $c)
                            @php
                                $s = (float) $empleados->sum(fn ($e) => (float) ($e->DescuentosDetalle[$c] ?? 0));
                                $generalDescuentos[$c] = ($generalDescuentos[$c] ?? 0) + $s;
                            @endphp
                            <td>{{ $fmtT($s) }}</td>
                        @endforeach
                        <td>{{ $fmtT($totDesc) }}</td>

                        <td>{{ $fmtT($totNeto) }}</td>
                    </tr>
                </tbody>
            </table>

            @php
                $generalTotalIng  += $totIng;
                $generalTotalDesc += $totDesc;
                $generalNeto      += $totNeto;
                $generalCantidad  += (int) $nomina['cantidadTrabajadores'];
            @endphp

            <table class="resumen">
                <tr>
                    <td class="txt">Total {{ $nombreCompania }} / {{ $nomina['nombreNomina'] }} del Rol de Pago</td>
                    <td class="val">{{ $fmtT($totNeto) }}</td>
                </tr>
                <tr>
                    <td class="sangria">Cantidad Trabajadores {{ $nombreCompania }} / {{ $nomina['nombreNomina'] }} del Rol de Pago</td>
                    <td class="val">{{ $nomina['cantidadTrabajadores'] }}</td>
                </tr>
            </table>
        @endforeach
    </div>

@empty
    <p>No se encontraron datos para los filtros seleccionados.</p>
@endforelse

{{-- ====== TOTAL GENERAL DE RUBROS ====== --}}
@if($bloques->isNotEmpty())
    <table class="general" style="page-break-inside: avoid;">
        <thead>
            <tr>
                <th></th>
                <th colspan="{{ count($generalIngresos) + 1 }}">INGRESO</th>
                <th colspan="{{ count($generalDescuentos) + 1 }}">DESCUENTOS</th>
            </tr>
            <tr>
                <th style="width: 44pt;"></th>
                @foreach($generalIngresos as $c => $v)
                    <th>{{ $c }}</th>
                @endforeach
                <th>TOTAL</th>
                @foreach($generalDescuentos as $c => $v)
                    <th>{{ $c }}</th>
                @endforeach
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="lbl">Total General Rubros</td>
                @foreach($generalIngresos as $c => $v)
                    <td>{{ $fmtD($v) }}</td>
                @endforeach
                <td>{{ $fmtD($generalTotalIng) }}</td>
                @foreach($generalDescuentos as $c => $v)
                    <td>{{ $fmtD($v) }}</td>
                @endforeach
                <td>{{ $fmtD($generalTotalDesc) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="resumen" style="margin-top: 14pt;">
        <tr>
            <td class="txt" style="width: 200pt;">Total General del Rol de Pago</td>
            <td class="val">{{ $fmtT($generalNeto) }}</td>
        </tr>
        <tr>
            <td class="txt" style="width: 200pt;">Cantidad Trabajadores del Rol de Pago</td>
            <td class="val">{{ $generalCantidad }}</td>
        </tr>
    </table>
@endif

</body>
</html>
