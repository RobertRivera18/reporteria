<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Nómina por Compañía</title>
    <style>
        @page {
            margin: 10px 15px;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8px;
            color: #000;
        }
        .seccion-nomina {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .titulo-nomina {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .tabla-pdf {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .tabla-pdf th, .tabla-pdf td {
            border: 1px solid #000;
            padding: 2px 3px;
            text-align: right;
            word-wrap: break-word;
            font-size: 7.5px;
        }
        .tabla-pdf th {
            background-color: #d9d9d9;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }
        .text-center { text-align: center !important; }
        .text-left { text-align: left !important; }
        .fw-bold { font-weight: bold; }
        .bg-gray { background-color: #ececec; }
        .total-row td {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .resumen-total {
            margin-top: 5px;
            font-weight: bold;
            font-size: 9px;
        }
    </style>
</head>
<body>

    @foreach($nominasGrouped as $nomina)
        <div class="seccion-nomina">
            {{-- Encabezado del tipo de Nómina/Compañía --}}
            <div class="titulo-nomina">
                {{ $nombreCompania }} / {{ $nomina['nombreNomina'] }}
            </div>

            <table class="tabla-pdf">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 65px;">Documento</th>
                        <th rowspan="2" style="width: 130px;">Empleado</th>
                        <th rowspan="2" style="width: 22px;">Días</th>
                        <th colspan="{{ count($nomina['conceptosIngresos']) + 1 }}">INGRESO</th>
                        <th colspan="{{ count($nomina['conceptosDescuentos']) + 1 }}">DESCUENTOS</th>
                        <th rowspan="2" style="width: 55px;">NETO A RECIBIR</th>
                    </tr>
                    <tr>
                        @foreach($nomina['conceptosIngresos'] as $ingreso)
                            <th>{{ $ingreso }}</th>
                        @endforeach
                        <th class="bg-gray">TOTAL</th>

                        @foreach($nomina['conceptosDescuentos'] as $descuento)
                            <th>{{ $descuento }}</th>
                        @endforeach
                        <th class="bg-gray">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalesIngresosConcepto = array_fill_keys($nomina['conceptosIngresos'], 0);
                        $totalesDescuentosConcepto = array_fill_keys($nomina['conceptosDescuentos'], 0);
                        $granTotalIngresos = 0;
                        $granTotalDescuentos = 0;
                        $granTotalNeto = 0;
                    @endphp

                    @foreach($nomina['empleados'] as $emp)
                        @php
                            $granTotalIngresos += $emp->TotalIngresos;
                            $granTotalDescuentos += $emp->TotalDescuentos;
                            $granTotalNeto += $emp->NetoARecibir;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $emp->NumeroDocumento }}</td>
                            <td class="text-left">{{ $emp->NombreCompleto }}</td>
                            <td class="text-center">{{ number_format($emp->Dias, 0) }}</td>

                            {{-- Ingresos --}}
                            @foreach($nomina['conceptosIngresos'] as $ingreso)
                                @php
                                    $val = $emp->IngresosDetalle[$ingreso] ?? 0;
                                    $totalesIngresosConcepto[$ingreso] += $val;
                                @endphp
                                <td>{{ $val > 0 ? number_format($val, 2) : '' }}</td>
                            @endforeach
                            <td class="fw-bold">{{ number_format($emp->TotalIngresos, 2) }}</td>

                            {{-- Descuentos --}}
                            @foreach($nomina['conceptosDescuentos'] as $descuento)
                                @php
                                    $val = $emp->DescuentosDetalle[$descuento] ?? 0;
                                    $totalesDescuentosConcepto[$descuento] += $val;
                                @endphp
                                <td>{{ $val > 0 ? number_format($val, 2) : '' }}</td>
                            @endforeach
                            <td class="fw-bold">{{ number_format($emp->TotalDescuentos, 2) }}</td>

                            {{-- Neto a Recibir --}}
                            <td class="fw-bold">{{ number_format($emp->NetoARecibir, 2) }}</td>
                        </tr>
                    @endforeach

                    {{-- Fila de Totales de la Nómina --}}
                    <tr class="total-row">
                        <td colspan="3" class="text-left">Total</td>

                        @foreach($nomina['conceptosIngresos'] as $ingreso)
                            <td>{{ number_format($totalesIngresosConcepto[$ingreso], 2) }}</td>
                        @endforeach
                        <td>{{ number_format($granTotalIngresos, 2) }}</td>

                        @foreach($nomina['conceptosDescuentos'] as $descuento)
                            <td>{{ number_format($totalesDescuentosConcepto[$descuento], 2) }}</td>
                        @endforeach
                        <td>{{ number_format($granTotalDescuentos, 2) }}</td>

                        <td>{{ number_format($granTotalNeto, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- Pie de resumen por tipo de nómina igual a la imagen --}}
            <div class="resumen-total">
                Total {{ $nombreCompania }} / {{ $nomina['nombreNomina'] }} del Rol de Pago: 
                <span style="margin-left: 15px;">{{ number_format($granTotalNeto, 2) }}</span>
            </div>
            <div class="resumen-total">
                Cantidad Trabajadores {{ $nombreCompania }} / {{ $nomina['nombreNomina'] }} del Rol de Pago: 
                <span style="margin-left: 15px;">{{ $nomina['cantidadTrabajadores'] }}</span>
            </div>
        </div>
    @endforeach

</body>
</html>