@php
$r = $this->resumen;
$m = fn ($v) => '$' . number_format((float) $v, 2, '.', ',');
$pc = fn ($v) => number_format((float) $v, 1, '.', ',') . '%';
@endphp

<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 text-gray-800 dark:text-gray-100">

    {{-- ===== Filtros ===== --}}
    <section aria-labelledby="filtros-titulo"
        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6"
        x-data="{ open: true }">

        {{-- Encabezado --}}
        <header
            class="flex items-center justify-between px-4 sm:px-5 py-3 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z" />
                </svg>
                <h2 id="filtros-titulo" class="text-sm font-semibold text-gray-900 dark:text-gray-100">Filtros</h2>

                {{-- Contador de filtros activos --}}
                @php
                $activos = collect([$id_compania, $nomina, $fecha_inicio, $fecha_fin])->filter()->count();
                @endphp
                @if($activos)
                <span
                    class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 text-xs font-medium rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                    {{ $activos }}
                </span>
                @endif
            </div>

            {{-- Colapsar en móvil --}}
            <button type="button" @click="open = !open"
                class="md:hidden text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                :aria-expanded="open">
                <span x-text="open ? 'Ocultar' : 'Mostrar'"></span>
            </button>
        </header>

        {{-- Formulario --}}
        <form wire:submit.prevent="$refresh" x-show="open" x-transition class="p-4 sm:p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">

                {{-- Compañía --}}
                <div class="lg:col-span-4">
                    <label for="f-compania" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                        Compañía
                    </label>
                    <select id="f-compania" wire:model.live="id_compania" class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm
                               focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition">
                        <option value="">Todas las compañías</option>
                        @foreach($companias as $c)
                        <option value="{{ $c['IdCompania'] }}">{{ $c['NombreCompania'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Nómina --}}
                <div class="lg:col-span-2">
                    <label for="f-nomina" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                        Nómina
                    </label>
                    <select id="f-nomina" wire:model="nomina" class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm
                               focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition">
                        <option value="">Todas</option>
                        @foreach($nominas as $n)
                        <option value="{{ $n }}">{{ $n }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Rango de fechas agrupado --}}
                <div class="sm:col-span-2 lg:col-span-4">
                    <span class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                        Rango de fechas
                    </span>
                    <div class="flex items-center gap-2">
                        <input type="date" id="f-desde" wire:model="fecha_inicio" aria-label="Fecha desde"
                            @if($fecha_fin) max="{{ $fecha_fin }}" @endif class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm
                                  focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition">
                        <span class="text-gray-400 text-sm" aria-hidden="true">→</span>
                        <input type="date" id="f-hasta" wire:model="fecha_fin" aria-label="Fecha hasta"
                            @if($fecha_inicio) min="{{ $fecha_inicio }}" @endif class="w-full h-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm
                                  focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition">
                    </div>
                    @error('fecha_fin')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Acciones --}}
                <div class="sm:col-span-2 lg:col-span-2 flex items-end gap-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="$refresh" class="flex-1 h-10 inline-flex items-center justify-center gap-2 px-4 rounded-lg text-sm font-medium
                               bg-indigo-600 text-white hover:bg-indigo-700 active:bg-indigo-800
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800
                               disabled:opacity-60 disabled:cursor-not-allowed transition">
                        <svg wire:loading wire:target="$refresh" class="w-4 h-4 animate-spin" fill="none"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        <span wire:loading.remove wire:target="$refresh">Actualizar</span>
                        <span wire:loading wire:target="$refresh">Consultando…</span>
                    </button>

                    @if($activos)
                    <button type="button" wire:click="limpiarFiltros" title="Limpiar filtros"
                        class="h-10 px-3 rounded-lg text-sm text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600
                                   hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        Limpiar
                    </button>
                    @endif
                </div>
            </div>

            {{-- Estado de carga accesible (al cambiar compañía) --}}
            <p wire:loading wire:target="id_compania" class="mt-3 text-xs text-gray-500 dark:text-gray-400"
                role="status">
                Actualizando opciones…
            </p>
        </form>
    </section>

    {{-- ===== Estados: error / vacío ===== --}}
    @if(isset($r['error']))
    <div
        class="rounded-md bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 p-4 text-sm text-red-700 dark:text-red-200">
        {{ $r['error'] }}
    </div>
    @elseif(isset($r['vacio']))
    <div
        class="rounded-md bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 text-sm text-gray-600 dark:text-gray-300">
        No hay nómina procesada para el período y filtros seleccionados. Prueba con otras fechas o con "Todas" las
        nóminas.
    </div>
    @else
    {{-- ===== Resumen principal ===== --}}
    <div class="mb-6">
        <p class="text-sm text-gray-500">{{ $r['compania'] }} · {{ \Carbon\Carbon::parse($fecha_inicio)->format('d/m/Y')
            }} – {{ \Carbon\Carbon::parse($fecha_fin)->format('d/m/Y') }}</p>
        <h2 class="text-3xl sm:text-5xl font-bold tracking-tight mt-1">
            <span class="text-emerald-600 dark:text-emerald-400">{{ $m($r['neto']) }}</span>
            <span class="text-gray-800 dark:text-gray-100">netos para {{ $r['personas'] }} personas</span>
        </h2>
        <p class="text-sm text-gray-500 mt-1">
            De {{ $m($r['ingresos']) }} en ingresos se descuentan {{ $m($r['descuentos']) }} ({{ $pc($r['pctDescuento'])
            }}).
        </p>

        {{-- Barra: ancho total = ingresos --}}
        @php $anchoNeto = $r['ingresos'] > 0 ? $r['neto'] / $r['ingresos'] * 100 : 0; @endphp
        <div class="flex h-8 rounded overflow-hidden mt-4 bg-gray-200 dark:bg-gray-700">
            <div class="bg-emerald-600 text-white text-xs font-semibold flex items-center px-3 whitespace-nowrap"
                style="width: {{ $anchoNeto }}%">{{ $m($r['neto']) }}</div>
            <div class="bg-orange-600 text-white text-xs font-semibold flex items-center px-3 whitespace-nowrap overflow-hidden"
                style="width: {{ 100 - $anchoNeto }}%">{{ $m($r['descuentos']) }}</div>
        </div>
        <div class="flex gap-4 mt-2 text-xs text-gray-500">
            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-600 mr-1"></span>Neto a recibir</span>
            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-orange-600 mr-1"></span>Descuentos</span>
        </div>
    </div>

    {{-- ===== KPIs ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        @foreach([
        ['Ingresos', $m($r['ingresos'])],
        ['Descuentos', $m($r['descuentos'])],
        ['Neto promedio por persona', $m($r['promedio'])],
        ['Nóminas', count($r['porNomina'])],
        ] as [$titulo, $valor])
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-xs text-gray-500">{{ $titulo }}</div>
            <div class="text-xl font-bold mt-1">{{ $valor }}</div>
        </div>
        @endforeach
    </div>

    {{-- ===== Neto por compañía (solo con "Todas las compañías") ===== --}}
    @if(count($r['porCompania']) > 1)
    @php $maxCia = max($r['porCompania']->max('neto'), 1); @endphp
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-4">
        <h3 class="font-semibold">Neto a pagar por compañía</h3>
        <p class="text-xs text-gray-500 mb-3">{{ count($r['porCompania']) }} compañías con nómina en el período</p>
        @foreach($r['porCompania'] as $c)
        <div class="flex items-center gap-3 my-2 text-sm">
            <div class="w-40 sm:w-60 truncate" title="{{ $c['nombre'] }}">{{ $c['nombre'] }}</div>
            <div class="flex-1 h-3.5 bg-gray-200 dark:bg-gray-700 rounded-sm overflow-hidden">
                <div class="h-full bg-emerald-600" style="width: {{ $c['neto'] / $maxCia * 100 }}%"></div>
            </div>
            <div class="w-28 text-right font-semibold tabular-nums">{{ $m($c['neto']) }}</div>
            <div class="w-16 text-right text-xs text-gray-500 hidden sm:block">{{ $c['personas'] }} pers.</div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ===== Neto por nómina + Descuentos por rubro ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mb-4">
        <div class="lg:col-span-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="font-semibold">Neto a pagar por nómina</h3>
            <p class="text-xs text-gray-500 mb-3">Ordenado de mayor a menor</p>
            @php $maxNeto = max($r['porNomina']->max('neto'), 1); @endphp
            @foreach($r['porNomina'] as $n)
            <div class="flex items-center gap-3 my-2 text-sm">
                <div class="w-40 sm:w-52 truncate" title="{{ $n['nombre'] }}">{{ $n['nombre'] }}</div>
                <div class="flex-1 h-3.5 bg-gray-200 dark:bg-gray-700 rounded-sm overflow-hidden">
                    <div class="h-full bg-emerald-600" style="width: {{ $n['neto'] / $maxNeto * 100 }}%"></div>
                </div>
                <div class="w-24 text-right font-semibold tabular-nums">{{ $m($n['neto']) }}</div>
            </div>
            @endforeach
        </div>

        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="font-semibold">¿En qué se van los descuentos?</h3>
            <p class="text-xs text-gray-500 mb-3">Por rubro, de mayor a menor</p>
            @php $maxDesc = max($r['rubrosDescuento'] ?: [1]); @endphp
            @forelse($r['rubrosDescuento'] as $concepto => $valor)
            <div class="flex items-center gap-3 my-2 text-sm">
                <div class="w-32 truncate" title="{{ $concepto }}">{{ $concepto }}</div>
                <div class="flex-1 h-3.5 bg-gray-200 dark:bg-gray-700 rounded-sm overflow-hidden">
                    <div class="h-full bg-orange-600" style="width: {{ $valor / $maxDesc * 100 }}%"></div>
                </div>
                <div class="w-20 text-right font-semibold tabular-nums">{{ $m($valor) }}</div>
            </div>
            @empty
            <p class="text-sm text-gray-500">Sin descuentos en el período.</p>
            @endforelse
        </div>
    </div>

    {{-- ===== Ingresos por rubro + rankings ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="font-semibold">Composición de ingresos</h3>
            <p class="text-xs text-gray-500 mb-3">Peso de cada rubro sobre el total</p>
            @foreach($r['rubrosIngresos'] as $concepto => $valor)
            @php $parte = $r['ingresos'] > 0 ? $valor / $r['ingresos'] * 100 : 0; @endphp
            <div class="my-2 text-sm">
                <div class="flex justify-between">
                    <span class="truncate pr-2">{{ $concepto }}</span>
                    <span class="tabular-nums font-semibold">{{ $m($valor) }} <span class="text-gray-400 font-normal">·
                            {{ $pc($parte) }}</span></span>
                </div>
                <div class="h-2 mt-1 bg-gray-200 dark:bg-gray-700 rounded-sm overflow-hidden">
                    <div class="h-full bg-indigo-600" style="width: {{ $parte }}%"></div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="font-semibold">Mayores descuentos por persona</h3>
            <p class="text-xs text-gray-500 mb-3">Top 5 del período</p>
            @foreach($r['topDescuento'] as $e)
            <div class="flex justify-between gap-2 py-2 border-t border-gray-100 dark:border-gray-700 text-sm">
                <div class="min-w-0">
                    <div class="truncate">{{ $e['nombre'] }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ $e['nomina'] }}</div>
                </div>
                <div class="text-right tabular-nums">
                    <div class="font-semibold text-orange-600 dark:text-orange-400">{{ $m($e['descuentos']) }}</div>
                    <div class="text-xs text-gray-500">{{ $pc($e['ingresos'] > 0 ? $e['descuentos'] / $e['ingresos'] *
                        100 : 0) }} de sus ingresos</div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="font-semibold">Mayores netos por persona</h3>
            <p class="text-xs text-gray-500 mb-3">Top 5 del período</p>
            @foreach($r['topNeto'] as $e)
            <div class="flex justify-between gap-2 py-2 border-t border-gray-100 dark:border-gray-700 text-sm">
                <div class="min-w-0">
                    <div class="truncate">{{ $e['nombre'] }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ $e['nomina'] }}</div>
                </div>
                <div class="font-semibold text-emerald-600 dark:text-emerald-400 tabular-nums">{{ $m($e['neto']) }}
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ===== Tabla de detalle ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="font-semibold mb-3">Detalle por nómina</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-2 pr-3 font-medium">Nómina</th>
                        <th class="text-right py-2 px-3 font-medium">Personas</th>
                        <th class="text-right py-2 px-3 font-medium">Ingresos</th>
                        <th class="text-right py-2 px-3 font-medium">Descuentos</th>
                        <th class="text-right py-2 px-3 font-medium">% desc.</th>
                        <th class="text-right py-2 px-3 font-medium">Neto</th>
                        <th class="text-right py-2 pl-3 font-medium">Neto prom.</th>
                    </tr>
                </thead>
                <tbody class="tabular-nums">
                    @foreach($r['porNomina'] as $n)
                    <tr class="border-b border-gray-100 dark:border-gray-700">
                        <td class="py-2 pr-3">{{ $n['nombre'] }}</td>
                        <td class="text-right py-2 px-3">{{ $n['personas'] }}</td>
                        <td class="text-right py-2 px-3">{{ $m($n['ingresos']) }}</td>
                        <td class="text-right py-2 px-3">{{ $m($n['descuentos']) }}</td>
                        <td class="text-right py-2 px-3">{{ $pc($n['ingresos'] > 0 ? $n['descuentos'] / $n['ingresos'] *
                            100 : 0) }}</td>
                        <td class="text-right py-2 px-3 font-semibold">{{ $m($n['neto']) }}</td>
                        <td class="text-right py-2 pl-3">{{ $m($n['personas'] ? $n['neto'] / $n['personas'] : 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="tabular-nums font-bold">
                    <tr>
                        <td class="py-2 pr-3">Total</td>
                        <td class="text-right py-2 px-3">{{ $r['personas'] }}</td>
                        <td class="text-right py-2 px-3">{{ $m($r['ingresos']) }}</td>
                        <td class="text-right py-2 px-3">{{ $m($r['descuentos']) }}</td>
                        <td class="text-right py-2 px-3">{{ $pc($r['pctDescuento']) }}</td>
                        <td class="text-right py-2 px-3">{{ $m($r['neto']) }}</td>
                        <td class="text-right py-2 pl-3">{{ $m($r['promedio']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif
</div>
