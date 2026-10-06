<div>
    {{-- Formulario de Filtros --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
            
            {{-- Fecha Inicio --}}
            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-gray-700 uppercase tracking-wider">Fecha Inicio</label>
                <input 
                    type="date" 
                    wire:model.live.debounce.500ms="fecha_inicio" 
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-colors"
                >
            </div>
            
            {{-- Fecha Fin --}}
            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-gray-700 uppercase tracking-wider">Fecha Fin</label>
                <input 
                    type="date" 
                    wire:model.live.debounce.500ms="fecha_fin" 
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-colors"
                >
            </div>

            {{-- Compañía --}}
            <div class="lg:col-span-3">
                <label class="mb-1 block text-xs font-semibold text-gray-700 uppercase tracking-wider">Compañía</label>
                <select 
                    wire:model.live="id_compania" 
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-colors"
                >
                    @foreach($companias as $comp)
                        <option value="{{ $comp->IdCompania }}" wire:key="comp-{{ $comp->IdCompania }}">
                            {{ $comp->NombreCompania }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Nómina --}}
            <div class="lg:col-span-3">
                <label class="mb-1 block text-xs font-semibold text-gray-700 uppercase tracking-wider">Nómina</label>
                <select 
                    wire:model.live="nomina" 
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-colors"
                >
                    <option value="">-- TODAS LAS NÓMINAS --</option>
                    @foreach($nominas as $nom)
                        <option value="{{ $nom }}" wire:key="nom-{{ $loop->index }}">{{ $nom }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Botón Exportar PDF --}}
            <div class="lg:col-span-2">
                <button 
                    wire:click="exportarPdf" 
                    wire:loading.attr="disabled" 
                    class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 disabled:opacity-50 transition-colors"
                >
                    {{-- Icono opcional / Texto cuando no está cargando --}}
                    <span wire:loading.remove wire:target="exportarPdf" class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar PDF
                    </span>

                    {{-- Loader en el botón --}}
                    <span wire:loading wire:target="exportarPdf" class="flex items-center gap-2">
                        <svg class="h-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Generando...
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Indicador de Carga --}}
    <div wire:loading wire:target="fecha_inicio, fecha_fin, id_compania, nomina" class="my-4 flex items-center justify-center gap-2 text-indigo-600">
        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-xs font-medium text-gray-500">Cargando reporte...</span>
    </div>

    {{-- Tablas Agrupadas por Nómina --}}
    @forelse($bloquesReporte as $index => $bloque)
        @php
            $etiquetaGrupo = "{$nombreCompania} / {$bloque['nombreNomina']}";
        @endphp

        <div class="mb-8 rounded-xl border border-gray-200 bg-white p-5 shadow-sm" wire:key="bloque-{{ $index }}">
            <h6 class="mb-4 text-xs font-bold uppercase tracking-wider text-indigo-600">
                {{ $etiquetaGrupo }}
            </h6>
            
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full text-center text-[11px] leading-tight text-gray-700">
                    <thead class="bg-gray-100 text-[10px] uppercase text-gray-700 font-bold tracking-wider">
                        <tr class="divide-x divide-gray-200 border-b border-gray-200">
                            <th rowspan="2" class="p-2 min-w-[90px] align-middle">Documento</th>
                            <th rowspan="2" class="p-2 min-w-[180px] align-middle text-left">Empleado</th>
                            <th rowspan="2" class="p-2 min-w-[45px] align-middle">Días</th>
                            <th colspan="{{ count($bloque['conceptosIngresos']) + 1 }}" class="p-2 bg-emerald-50 text-emerald-800 border-b border-gray-200">INGRESO</th>
                            <th colspan="{{ count($bloque['conceptosDescuentos']) + 1 }}" class="p-2 bg-rose-50 text-rose-800 border-b border-gray-200">DESCUENTOS</th>
                            <th rowspan="2" class="p-2 min-w-[85px] align-middle bg-gray-200/60">NETO A RECIBIR</th>
                        </tr>
                        <tr class="divide-x divide-gray-200">
                            {{-- Encabezados Ingresos --}}
                            @foreach($bloque['conceptosIngresos'] as $ing)
                                <th class="p-2 bg-emerald-50/50" wire:key="head-ing-{{ $loop->index }}">{{ $ing }}</th>
                            @endforeach
                            <th class="p-2 bg-emerald-100 text-emerald-900 font-extrabold">TOTAL</th>

                            {{-- Encabezados Descuentos --}}
                            @foreach($bloque['conceptosDescuentos'] as $desc)
                                <th class="p-2 bg-rose-50/50" wire:key="head-desc-{{ $loop->index }}">{{ $desc }}</th>
                            @endforeach
                            <th class="p-2 bg-rose-100 text-rose-900 font-extrabold">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @php
                            $totalesIngresosConcepto = array_fill_keys($bloque['conceptosIngresos'], 0);
                            $totalesDescuentosConcepto = array_fill_keys($bloque['conceptosDescuentos'], 0);
                            $granTotalIngresos = 0;
                            $granTotalDescuentos = 0;
                            $granTotalNeto = 0;
                        @endphp

                        @foreach($bloque['empleados'] as $emp)
                            @php
                                $granTotalIngresos += $emp->TotalIngresos;
                                $granTotalDescuentos += $emp->TotalDescuentos;
                                $granTotalNeto += $emp->NetoARecibir;
                            @endphp
                            <tr class="divide-x divide-gray-200 hover:bg-gray-50/80 transition-colors" wire:key="emp-{{ $emp->NumeroDocumento ?? $loop->index }}">
                                <td class="p-2 whitespace-nowrap text-gray-500 font-mono">{{ $emp->NumeroDocumento }}</td>
                                <td class="p-2 whitespace-nowrap text-left font-medium text-gray-900">{{ $emp->NombreCompleto }}</td>
                                <td class="p-2 whitespace-nowrap">{{ number_format($emp->Dias, 0) }}</td>

                                {{-- Ingresos --}}
                                @foreach($bloque['conceptosIngresos'] as $ing)
                                    @php
                                        $val = $emp->IngresosDetalle[$ing] ?? 0;
                                        $totalesIngresosConcepto[$ing] += $val;
                                    @endphp
                                    <td class="p-2 whitespace-nowrap text-right text-gray-600">{{ $val > 0 ? number_format($val, 2) : '' }}</td>
                                @endforeach
                                <td class="p-2 whitespace-nowrap text-right font-bold text-emerald-700 bg-emerald-50/30">
                                    {{ number_format($emp->TotalIngresos, 2) }}
                                </td>

                                {{-- Descuentos --}}
                                @foreach($bloque['conceptosDescuentos'] as $desc)
                                    @php
                                        $val = $emp->DescuentosDetalle[$desc] ?? 0;
                                        $totalesDescuentosConcepto[$desc] += $val;
                                    @endphp
                                    <td class="p-2 whitespace-nowrap text-right text-gray-600">{{ $val > 0 ? number_format($val, 2) : '' }}</td>
                                @endforeach
                                <td class="p-2 whitespace-nowrap text-right font-bold text-rose-700 bg-rose-50/30">
                                    {{ number_format($emp->TotalDescuentos, 2) }}
                                </td>

                                {{-- Neto --}}
                                <td class="p-2 whitespace-nowrap text-right font-extrabold text-gray-900 bg-gray-50">
                                    {{ number_format($emp->NetoARecibir, 2) }}
                                </td>
                            </tr>
                        @endforeach

                        {{-- Fila Totales --}}
                        <tr class="divide-x divide-gray-200 bg-gray-100 font-bold text-right text-gray-900">
                            <td colspan="3" class="p-2 text-left uppercase tracking-wider text-[10px]">Total</td>
                            
                            {{-- Totales Ingresos --}}
                            @foreach($bloque['conceptosIngresos'] as $ing)
                                <td class="p-2">{{ number_format($totalesIngresosConcepto[$ing], 2) }}</td>
                            @endforeach
                            <td class="p-2 bg-emerald-200/60 text-emerald-950">{{ number_format($granTotalIngresos, 2) }}</td>

                            {{-- Totales Descuentos --}}
                            @foreach($bloque['conceptosDescuentos'] as $desc)
                                <td class="p-2">{{ number_format($totalesDescuentosConcepto[$desc], 2) }}</td>
                            @endforeach
                            <td class="p-2 bg-rose-200/60 text-rose-950">{{ number_format($granTotalDescuentos, 2) }}</td>

                            {{-- Total Neto --}}
                            <td class="p-2 bg-gray-300/80 text-gray-950 font-extrabold">{{ number_format($granTotalNeto, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Pie de bloque con resumen --}}
            <div class="mt-4 flex flex-col items-center justify-center gap-1 border-t border-gray-100 pt-3 text-xs font-semibold text-gray-700 sm:flex-row sm:gap-6">
                <div>
                    Total <span class="text-indigo-600">{{ $etiquetaGrupo }}</span> del Rol de Pago: 
                    <span class="ml-1 text-sm font-bold text-gray-900">${{ number_format($granTotalNeto, 2) }}</span>
                </div>
                <div class="hidden text-gray-300 sm:block">•</div>
                <div>
                    Cantidad Trabajadores: 
                    <span class="ml-1 text-sm font-bold text-gray-900">{{ $bloque['cantidadTrabajadores'] }}</span>
                </div>
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-6 text-center text-sm text-blue-700 shadow-sm">
            <svg class="mx-auto mb-2 h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            No se encontraron registros de nómina con los filtros seleccionados.
        </div>
    @endforelse
</div>