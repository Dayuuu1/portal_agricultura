{{--
    Printable report of «Estadísticas del portal» (StatisticsReportController@show), laid out on A4 so it can be printed or
    saved as PDF from the browser. Charts are drawn with CSS so they print without JavaScript.
--}}
@php
    use App\Enums\SiteEventType;
    use App\Models\Setting;
    use App\Support\Analytics\SiteStatistics;
@endphp

@php
    $period = $report->period;
    $siteName = Setting::valueOf('site_name') ?? config('app.name');
    $summary = $report->summary();
    $trend = $report->trendBuckets();
    $trendMax = max([1, ...array_column($trend['buckets'], 'views')]);
    $busiest = collect($trend['buckets'])->sortByDesc('visitors')->first();
    $hours = $report->hours();
    $hoursMax = max([1, ...$hours]);
    $peakHour = $report->peakHour();
    $searchTotals = $report->searchTotals();
    $format = fn (int|float $value, int $precision = 0): string => SiteStatistics::formatNumber($value, $precision);
    $labelEvery = max(1, (int) ceil(count($trend['buckets']) / 16));
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Informe de estadísticas · {{ $period->label() }}</title>
    @fonts
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 12mm; }
        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @media print {
            .report-sheet { margin: 0 !important; padding: 0 !important; box-shadow: none !important; max-width: none !important; }
        }
    </style>
</head>
<body class="bg-slate-200/70 text-slate-700 print:bg-white">
    <div class="sticky top-0 z-10 border-b border-slate-200 bg-white/95 backdrop-blur print:hidden">
        <div class="mx-auto flex max-w-[210mm] items-center justify-between gap-4 px-4 py-3">
            <p class="text-sm text-slate-600"><span class="font-semibold text-ink">Vista de impresión.</span> Use «Guardar como PDF» como destino para obtener el archivo.</p>
            <div class="flex shrink-0 gap-2">
                <a href="{{ route('filament.admin.pages.site-analytics') }}" class="btn-secondary px-3 py-2 text-xs">Volver</a>
                <button type="button" onclick="window.print()" class="btn-primary px-3 py-2 text-xs">
                    <x-heroicon-o-printer class="size-4" /> Imprimir o guardar PDF
                </button>
            </div>
        </div>
    </div>

    <main class="report-sheet mx-auto my-8 max-w-[210mm] space-y-7 bg-white p-[12mm] shadow-xl">
        {{-- Encabezado institucional --}}
        <header class="flex items-start justify-between gap-6 border-b-4 border-brand-700 pb-5">
            <div class="flex items-center gap-4">
                <img src="{{ Setting::logoUrl() }}" alt="" class="h-14 w-auto">
                <div>
                    <p class="text-[0.65rem] font-bold tracking-[0.14em] text-brand-700 uppercase">Informe de estadísticas del portal web</p>
                    <h1 class="font-display text-xl leading-tight font-extrabold text-ink">{{ $siteName }}</h1>
                </div>
            </div>
            <dl class="shrink-0 space-y-1 text-right text-[0.7rem]">
                <div><dt class="inline font-semibold text-slate-500">Periodo:</dt> <dd class="inline font-semibold text-ink">{{ $period->label() }}</dd></div>
                <div><dt class="inline text-slate-500">Comparado con:</dt> <dd class="inline">{{ \Illuminate\Support\Str::lcfirst($period->previous()->label()) }}</dd></div>
                <div><dt class="inline text-slate-500">Generado:</dt> <dd class="inline">{{ now()->format('d/m/Y H:i') }} · {{ $generatedBy }}</dd></div>
            </dl>
        </header>

        {{-- 1. Indicadores clave --}}
        <section class="break-inside-avoid">
            <h2 class="mb-3 font-display text-base font-bold text-ink">1. Indicadores clave</h2>
            <div class="grid grid-cols-3 gap-3">
                @foreach ($report->indicators() as $indicator)
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-[0.68rem] font-semibold text-slate-500">{{ $indicator['label'] }}</p>
                        <p class="mt-1 font-display text-2xl leading-none font-extrabold text-ink tabular-nums">{{ $indicator['value'] }}</p>
                        <p class="mt-2 flex items-center gap-1.5 text-[0.65rem]">
                            @if ($indicator['change'] === null)
                                <span class="text-slate-400">Sin periodo anterior para comparar</span>
                            @else
                                <span @class([
                                    'rounded px-1.5 py-0.5 font-bold tabular-nums',
                                    'bg-emerald-100 text-emerald-800' => $indicator['improved'] === true,
                                    'bg-red-100 text-red-800' => $indicator['improved'] === false,
                                    'bg-slate-100 text-slate-600' => $indicator['improved'] === null,
                                ])>{{ $indicator['change'] > 0 ? '▲ +' : ($indicator['change'] < 0 ? '▼ ' : '') }}{{ $format($indicator['change'], 1) }} %</span>
                                <span class="text-slate-500">antes: {{ $indicator['previousValue'] }}</span>
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
            <p class="mt-2 text-[0.62rem] text-slate-500">
                {{ $format($searchTotals['searches']) }} búsquedas ({{ $format($searchTotals['withoutResults']) }} sin resultados) ·
                {{ $format($report->eventTotal(SiteEventType::Download)) }} descargas de documentos ·
                {{ $format($report->eventTotal(SiteEventType::Outbound)) }} clics a sitios externos ·
                {{ $format($report->eventTotal(SiteEventType::NotFound)) }} visitas a direcciones inexistentes.
            </p>
        </section>

        {{-- 2. Evolución --}}
        <section class="break-inside-avoid">
            <h2 class="mb-1 font-display text-base font-bold text-ink">2. Evolución de las visitas</h2>
            <p class="mb-3 text-[0.7rem] text-slate-500">
                Páginas vistas (barra clara) y visitantes (barra verde) por {{ $trend['unit'] }}.
                @if ($busiest && $busiest['visitors'] > 0)
                    Mayor afluencia: {{ $busiest['label'] }}, con {{ $format($busiest['visitors']) }} visitantes.
                @endif
            </p>
            <div class="flex h-36 items-end gap-px border-b border-slate-300" role="img" aria-label="Gráfico de visitas por {{ $trend['unit'] }}">
                @foreach ($trend['buckets'] as $bucket)
                    <div class="relative flex h-full flex-1 items-end" title="{{ $bucket['label'] }}: {{ $bucket['visitors'] }} visitantes, {{ $bucket['views'] }} páginas vistas">
                        <span class="absolute inset-x-0 bottom-0 rounded-t-sm bg-gold-300/70" style="height: {{ round($bucket['views'] / $trendMax * 100, 1) }}%"></span>
                        <span class="relative mx-auto w-3/5 rounded-t-sm bg-brand-600" style="height: {{ round($bucket['visitors'] / $trendMax * 100, 1) }}%"></span>
                    </div>
                @endforeach
            </div>
            <div class="mt-1 flex gap-px text-[0.55rem] text-slate-400">
                @foreach ($trend['buckets'] as $index => $bucket)
                    <span class="flex-1 truncate text-center">{{ $index % $labelEvery === 0 ? $bucket['label'] : '' }}</span>
                @endforeach
            </div>
        </section>

        {{-- 3. Origen y dispositivos --}}
        <div class="grid grid-cols-2 gap-6">
            <div>
                <h2 class="mb-3 font-display text-base font-bold text-ink">3. De dónde llegan</h2>
                <x-analytics.bars title="Origen de las visitas" unit="Visitas"
                    :rows="array_map(fn (array $source): array => ['label' => $source['label'], 'hint' => $source['topSite'] ? 'Sobre todo '.$source['topSite'] : null, 'value' => $source['visits'], 'display' => $format($source['visits']).' · '.$format($source['share'], 1).' %'], $report->sources())" />
            </div>
            <div>
                <h2 class="mb-3 font-display text-base font-bold text-ink">4. Con qué equipo</h2>
                <x-analytics.bars title="Dispositivos" unit="Visitantes"
                    :rows="array_map(fn (array $row): array => ['label' => $row['label'], 'value' => $row['visitors'], 'display' => $format($row['visitors']).' · '.$format($row['share'], 1).' %'], $report->technology('device'))" />
                <x-analytics.bars class="mt-4" title="Navegadores" unit="Visitantes"
                    :rows="array_map(fn (array $row): array => ['label' => $row['label'], 'value' => $row['visitors'], 'display' => $format($row['share'], 1).' %'], array_slice($report->technology('browser'), 0, 5))" />
            </div>
        </div>

        {{-- 5. Contenido --}}
        <section class="break-inside-avoid">
            <h2 class="mb-3 font-display text-base font-bold text-ink">5. Páginas más vistas</h2>
            @php $pages = $report->pages(); @endphp
            @if ($pages === [])
                <p class="text-xs text-slate-400 italic">Sin datos en el periodo.</p>
            @else
                <table class="w-full table-fixed text-left text-[0.7rem]">
                    <thead>
                        <tr class="border-b-2 border-brand-700 text-[0.62rem] tracking-wide text-slate-500 uppercase">
                            <th class="w-7 py-1.5">#</th>
                            <th class="py-1.5">Página</th>
                            <th class="w-16 py-1.5 text-right">Vistas</th>
                            <th class="w-20 py-1.5 text-right">Visitantes</th>
                            <th class="w-24 py-1.5 text-right">Tiempo medio</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($pages as $index => $page)
                            <tr class="break-inside-avoid">
                                <td class="py-1.5 text-slate-400 tabular-nums">{{ $index + 1 }}</td>
                                <td class="py-1.5 pr-3">
                                    <p class="truncate font-medium text-slate-800">{{ $page['title'] ?? $page['path'] }}</p>
                                    <p class="truncate text-[0.6rem] text-slate-400">{{ $page['path'] }}</p>
                                </td>
                                <td class="py-1.5 text-right font-semibold tabular-nums">{{ $format($page['views']) }}</td>
                                <td class="py-1.5 text-right tabular-nums">{{ $format($page['visitors']) }}</td>
                                <td class="py-1.5 text-right tabular-nums">{{ SiteStatistics::formatDuration($page['duration']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <div class="grid grid-cols-2 gap-6">
            <x-analytics.bars title="6. Secciones del portal" unit="Vistas"
                :rows="array_map(fn (array $section): array => ['label' => $section['label'], 'value' => $section['views'], 'display' => $format($section['views']).' · '.$format($section['share'], 1).' %'], $report->sections())" />

            <section class="break-inside-avoid">
                <h3 class="mb-2 flex items-baseline justify-between gap-3 border-b border-slate-200 pb-1.5 text-sm font-bold text-ink">
                    7. Horas de mayor visita
                    <span class="text-[0.65rem] font-semibold tracking-wide text-slate-400 uppercase">Páginas vistas</span>
                </h3>
                <div class="flex h-24 items-end gap-0.5">
                    @foreach ($hours as $hour => $views)
                        <span @class(['flex-1 rounded-t-sm', 'bg-gold-400' => $hour === $peakHour, 'bg-brand-600' => $hour !== $peakHour]) style="height: {{ max(1, round($views / $hoursMax * 100, 1)) }}%" title="{{ $hour }}:00 — {{ $views }}"></span>
                    @endforeach
                </div>
                <div class="mt-1 flex justify-between text-[0.55rem] text-slate-400"><span>00h</span><span>06h</span><span>12h</span><span>18h</span><span>23h</span></div>
                <p class="mt-1.5 text-[0.62rem] text-slate-500">
                    {{ $peakHour === null ? 'Sin visitas en el periodo.' : 'Hora pico: de '.str_pad((string) $peakHour, 2, '0', STR_PAD_LEFT).':00 a '.str_pad((string) (($peakHour + 1) % 24), 2, '0', STR_PAD_LEFT).':00 (hora de Perú).' }}
                </p>
            </section>
        </div>

        {{-- Búsquedas --}}
        <div class="grid grid-cols-2 gap-6">
            <x-analytics.bars title="8. Lo que más buscan" unit="Búsquedas"
                :rows="array_map(fn (array $search): array => ['label' => $search['term'], 'hint' => $search['scopes'].' · '.($search['results'] > 0 ? $format($search['results']).' resultados' : 'sin resultados'), 'value' => $search['searches']], $report->searches())" />
            <x-analytics.bars title="9. Búsquedas sin resultados" unit="Búsquedas" empty="Ninguna búsqueda se quedó sin resultados."
                note="Contenido que la ciudadanía busca y no encuentra: conviene publicarlo o usar esas palabras en los títulos."
                :rows="array_map(fn (array $search): array => ['label' => $search['term'], 'hint' => $search['scopes'], 'value' => $search['searches']], $report->searches(withoutResults: true))" />
        </div>

        {{-- Documentos --}}
        <div class="grid grid-cols-2 gap-6">
            <x-analytics.bars title="10. Documentos más consultados" unit="Vistas"
                :rows="array_map(fn (array $page): array => ['label' => $page['title'] ?? $page['path'], 'hint' => SiteStatistics::formatDuration($page['duration']).' de media', 'value' => $page['views']], $report->documentViews())" />
            <x-analytics.bars title="11. Documentos más descargados" unit="Descargas"
                :rows="array_map(fn (array $document): array => ['label' => $document['title'], 'hint' => $document['type'], 'value' => $document['downloads']], $report->downloads())" />
        </div>

        {{-- Enlaces --}}
        <div class="grid grid-cols-2 gap-6">
            <x-analytics.bars title="12. Enlaces a otros sitios" unit="Clics"
                :rows="collect($report->outboundLinks())->map(fn (int $clicks, string $site): array => ['label' => $site, 'value' => $clicks])->values()->all()" />
            <x-analytics.bars title="13. Direcciones que no existen" unit="Visitas" empty="Ningún visitante llegó a una dirección inexistente."
                note="Se pueden redirigir a la página correcta desde «Redirecciones» en el panel."
                :rows="collect($report->notFound())->map(fn (int $visits, string $path): array => ['label' => $path, 'value' => $visits])->values()->all()" />
        </div>

        <footer class="break-inside-avoid border-t border-slate-200 pt-4 text-[0.6rem] leading-relaxed text-slate-500">
            <p class="font-semibold text-slate-600">Cómo se obtienen estas cifras</p>
            <p>
                Estadísticas propias del portal, sin cookies ni servicios externos. Un visitante es un navegador distinto en un mismo día (quien vuelve otro día se cuenta de nuevo);
                una visita es una serie de páginas sin pausas de más de 30 minutos; el tiempo cuenta solo mientras la página está visible, hasta 30 minutos por página.
                No se cuentan robots ni personal del panel, y no se guarda la IP ni ningún dato personal (Ley N.° 29733). Las listas muestran los {{ \App\Support\Analytics\StatisticsReport::PRINT_LIMIT }} primeros; el Excel trae las listas completas.
            </p>
        </footer>
    </main>
</body>
</html>
