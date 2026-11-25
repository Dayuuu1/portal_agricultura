@php
    $sectionLabel = $filters['section']?->getLabel();
    $heading = $activeType?->name ?? ($isAgrarian ? 'Reportes de información agraria' : $sectionLabel) ?? 'Todos los documentos';
    $layout = $statisticsYear['layout'] ?? null;
    // Agrarian types have their own address (/informacion-agraria/{tipo}); the rest are filters of /documentos.
    $typeHasOwnPage = (bool) $activeType?->isAgrarianInformation();
    $baseQuery = array_filter([
        'seccion' => $filters['section']?->value,
        'tipo' => $typeHasOwnPage ? null : $filters['type'],
        'q' => $filters['term'],
        'vista' => $layout === 'indicador' ? 'indicador' : null,
    ]);
    $listingUrl = fn (array $query): string => $typeHasOwnPage
        ? $activeType->publicUrl(array_filter($query, fn ($value): bool => $value !== null))
        : route('documents.index', array_filter($query, fn ($value): bool => $value !== null));
    $breadcrumbs = match (true) {
        $isAgrarian => ['Información agraria' => route('agrarian-information'), $heading => null],
        (bool) $activeType => ['Documentos' => route('documents.index'), $activeType->section->getLabel() => route('documents.index', ['seccion' => $activeType->section->value]), $heading => null],
        default => ['Documentos' => route('documents.index'), $heading => null],
    };
    $chipClasses = fn (bool $active): string => $active
        ? 'bg-brand-600 text-white ring-brand-600 shadow-sm shadow-brand-900/20'
        : 'bg-white text-slate-700 ring-slate-200 hover:-translate-y-px hover:ring-brand-300 hover:text-brand-800';
    $sidebarLink = fn (bool $active): string => $active ? 'bg-brand-600 text-white' : 'text-slate-700 hover:bg-slate-100';
@endphp

<x-layouts.app :title="$heading" :nav="$isAgrarian ? 'informacion' : null"
    :description="$activeType?->description ?: ($isAgrarian ? 'Reportes estadísticos agrarios de Madre de Dios.' : 'Resoluciones, documentos de gestión, planeamiento, CMN, boletines y demás documentos públicos.')">
    <x-page-header :title="$heading" :breadcrumbs="$breadcrumbs"
        :subtitle="$activeType?->description ?: ($isAgrarian ? 'Busque y descargue los reportes estadísticos publicados por la Dirección Regional.' : 'Busque y descargue resoluciones, documentos de gestión, planes e informes publicados por la entidad.')" />
    <x-analytics-search :term="$filters['term']" :results="$documents?->total() ?? 0" scope="documentos" />

    <div class="container-site mt-10 grid grid-cols-1 gap-8 lg:grid-cols-4">
        {{-- Filtros --}}
        {{-- En escritorio la lista siempre está visible (sin depender de JavaScript); en pantallas pequeñas se despliega con el botón. --}}
        <aside class="lg:col-span-1" aria-label="Filtros de documentos" x-data="{ open: false }">
            @php($sidebarTitle = $isAgrarian ? 'Temas' : 'Categorías')
            <div class="card p-5 lg:sticky lg:top-24">
                <h2 class="hidden font-display text-base font-bold text-ink lg:block">{{ $sidebarTitle }}</h2>
                <button type="button" @click="open = ! open" aria-expanded="false" :aria-expanded="open.toString()" aria-controls="categorias-documentos"
                    class="flex min-h-11 w-full items-center justify-between font-display text-base font-bold text-ink lg:hidden">
                    {{ $sidebarTitle }}
                    <x-heroicon-o-chevron-down class="size-5 transition" ::class="open && 'rotate-180'" />
                </button>
                <div id="categorias-documentos" class="mt-3 max-lg:hidden" :class="{ 'max-lg:hidden': ! open }">
                @if ($isAgrarian)
                    <a href="{{ route('agrarian-information') }}" class="mb-1 flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50">
                        <x-heroicon-o-squares-2x2 class="size-4" /> Resumen de información agraria
                    </a>
                    <a href="{{ route('documents.index', ['seccion' => \App\Enums\DocumentSection::InformacionAgraria->value, 'vista' => 'lista']) }}" @class(['mb-2 block rounded-lg px-3 py-2 text-sm font-semibold', $sidebarLink(! $activeType)])>
                        Todos los reportes
                    </a>
                @else
                    <a href="{{ route('documents.index') }}" class="mb-1 flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50">
                        <x-heroicon-o-squares-2x2 class="size-4" /> Ver por categorías
                    </a>
                    <a href="{{ route('documents.index', ['vista' => 'lista']) }}" @class(['mb-2 block rounded-lg px-3 py-2 text-sm font-semibold', $sidebarLink(! $filters['section'] && ! $filters['type'])])>
                        Todos los documentos
                    </a>
                @endif
                @foreach (\App\Enums\DocumentSection::cases() as $section)
                    @continue(! $typesBySection->has($section->value))
                    <div class="mt-4">
                        @unless ($isAgrarian)
                            <a href="{{ $section === \App\Enums\DocumentSection::InformacionAgraria ? route('agrarian-information') : route('documents.index', ['seccion' => $section->value, 'vista' => 'lista']) }}" @class(['block px-3 text-sm font-semibold', 'text-brand-700' => $filters['section'] === $section, 'text-slate-500 hover:text-brand-700' => $filters['section'] !== $section])>
                                {{ $section->getLabel() }}
                            </a>
                        @endunless
                        <ul class="mt-1.5 space-y-0.5">
                            @foreach ($typesBySection[$section->value] as $type)
                                <li>
                                    <a href="{{ $type->publicUrl() }}" @if ($filters['type'] === $type->slug) aria-current="page" @endif
                                        @class(['flex items-center justify-between gap-2 rounded-lg px-3 py-1.5 text-sm', 'bg-brand-50 font-semibold text-brand-800' => $filters['type'] === $type->slug, 'text-slate-700 hover:bg-slate-100' => $filters['type'] !== $type->slug])>
                                        <span>{{ $type->name }}</span>
                                        <span class="rounded-full bg-slate-100 px-2 text-xs text-slate-500">{{ $type->documents_count }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
                </div>
            </div>
        </aside>

        <div class="lg:col-span-3">
            @if ($activeType && $yearCounts->isNotEmpty())
                <dl class="card mb-6 grid grid-cols-3 divide-x divide-slate-100">
                    <div class="px-4 py-4 sm:px-6">
                        <dt class="text-xs text-slate-500">Documentos</dt>
                        <dd class="mt-0.5 font-display text-xl font-extrabold text-brand-700 sm:text-2xl">{{ number_format($yearCounts->sum()) }}</dd>
                    </div>
                    <div class="px-4 py-4 sm:px-6">
                        <dt class="text-xs text-slate-500">Periodo</dt>
                        <dd class="mt-0.5 font-display text-xl font-extrabold text-brand-700 sm:text-2xl">
                            {{ $yearCounts->keys()->last() }}@if ($yearCounts->count() > 1)<span aria-hidden="true">–</span><span class="sr-only"> a </span>{{ $yearCounts->keys()->first() }}@endif
                        </dd>
                    </div>
                    <div class="px-4 py-4 sm:px-6">
                        <dt class="text-xs text-slate-500">Última publicación</dt>
                        <dd class="mt-0.5 font-display text-base font-bold text-ink sm:text-xl">
                            @if ($lastPublishedOn)
                                <time datetime="{{ $lastPublishedOn->toDateString() }}">{{ $lastPublishedOn->translatedFormat('j M Y') }}</time>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            @endif

            <form method="GET" action="{{ $typeHasOwnPage ? $activeType->publicUrl() : route('documents.index') }}" class="card mb-4 grid gap-3 p-4 sm:grid-cols-[1fr_auto]" role="search">
                @if ($filters['section'])
                    <input type="hidden" name="seccion" value="{{ $filters['section']->value }}">
                    <input type="hidden" name="vista" value="lista">
                @endif
                @if ($filters['type'] && ! $typeHasOwnPage)<input type="hidden" name="tipo" value="{{ $filters['type'] }}">@endif
                <div class="relative">
                    <label for="q" class="sr-only">{{ $isAgrarian ? 'Buscar reportes' : 'Buscar documentos' }}</label>
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-slate-400" />
                    <input type="search" id="q" name="q" value="{{ $filters['term'] }}" placeholder="Título, número o código…" class="form-input pl-10">
                </div>
                <button type="submit" class="btn-primary">Buscar</button>
            </form>

            @if ($yearCounts->isNotEmpty())
                <nav aria-label="Filtrar por año" class="mb-6">
                    <p class="mb-2 text-sm font-semibold text-slate-600">Año</p>
                    <ul class="flex flex-wrap gap-2">
                        <li>
                            <a href="{{ $listingUrl([...\Illuminate\Support\Arr::except($baseQuery, 'vista'), 'anio' => 'todos']) }}"
                                class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-semibold ring-1 transition {{ $chipClasses($filters['year'] === null) }}">
                                Todos
                                <span class="text-xs opacity-75">{{ $yearCounts->sum() }}</span>
                            </a>
                        </li>
                        @foreach ($yearCounts as $year => $total)
                            <li>
                                <a href="{{ $listingUrl([...$baseQuery, 'anio' => $year]) }}" @if ($filters['year'] === (int) $year) aria-current="true" @endif
                                    class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-semibold ring-1 transition {{ $chipClasses($filters['year'] === (int) $year) }}">
                                    {{ $year }}
                                    <span class="text-xs opacity-75">{{ $total }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            @if ($statisticsYear)
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-slate-500" aria-live="polite">
                        {{ $statisticsYear['total'] }} {{ \Illuminate\Support\Str::plural('documento', $statisticsYear['total']) }} del año <strong class="text-slate-700">{{ $filters['year'] }}</strong>
                    </p>
                    @if ($statisticsYear['offersSeries'])
                        <nav aria-label="Organizar publicaciones" class="inline-flex gap-1 rounded-xl bg-white p-1 ring-1 ring-slate-200">
                            @foreach (['mes' => ['Por mes', 'calendar-days'], 'indicador' => ['Por indicador', 'squares-2x2']] as $value => [$label, $icon])
                                <a href="{{ $listingUrl([...\Illuminate\Support\Arr::except($baseQuery, 'vista'), 'anio' => $filters['year'], 'vista' => $value === 'indicador' ? $value : null]) }}"
                                    @if ($layout === $value) aria-current="page" @endif
                                    @class([
                                        'inline-flex min-h-10 items-center gap-1.5 rounded-lg px-3 text-sm font-semibold transition',
                                        'bg-brand-600 text-white shadow-sm shadow-brand-900/20' => $layout === $value,
                                        'text-slate-600 hover:bg-slate-100 hover:text-brand-800' => $layout !== $value,
                                    ])>
                                    <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-4" />
                                    {{ $label }}
                                </a>
                            @endforeach
                        </nav>
                    @endif
                </div>

                @if ($statisticsYear['total'] === 0)
                    <x-empty-state icon="heroicon-o-chart-bar" title="No hay publicaciones en {{ $filters['year'] }}" message="Elija otro año para ver sus publicaciones." />
                @elseif ($layout === 'indicador')
                    <div class="space-y-4">
                        @foreach ($statisticsYear['series'] as $series)
                            <x-document-series :series="$series" :year="$filters['year']" />
                        @endforeach

                        @if ($statisticsYear['singles']->isNotEmpty())
                            <section class="card overflow-hidden" aria-labelledby="otras-publicaciones">
                                <h3 id="otras-publicaciones" class="px-5 pt-5 font-display text-base font-bold text-ink sm:px-6 sm:text-lg">Otras publicaciones</h3>
                                <ul class="mt-2 divide-y divide-slate-100">
                                    @foreach ($statisticsYear['singles'] as $document)
                                        <x-document-row :document="$document" />
                                    @endforeach
                                </ul>
                            </section>
                        @endif
                    </div>
                @else
                    <x-document-releases :releases="$statisticsYear['releases']" />
                @endif
            @else
                <p class="mb-3 text-sm text-slate-500" aria-live="polite">
                    {{ $documents->total() }} {{ \Illuminate\Support\Str::plural('documento', $documents->total()) }}
                    @if ($filters['year']) del año <strong class="text-slate-700">{{ $filters['year'] }}</strong>@endif
                    @if ($filters['term']) para «{{ $filters['term'] }}»@endif
                </p>

                @if ($documents->isNotEmpty())
                    <ul class="card divide-y divide-slate-100 overflow-hidden">
                        @foreach ($documents as $document)
                            <x-document-item :document="$document" :compact="(bool) $activeType" />
                        @endforeach
                    </ul>
                    <div class="mt-8">{{ $documents->onEachSide(1)->links() }}</div>
                @else
                    <x-empty-state icon="heroicon-o-document-text" title="No se encontraron documentos" message="Ajuste los filtros o intente con otro término de búsqueda." />
                @endif
            @endif
        </div>
    </div>
</x-layouts.app>
