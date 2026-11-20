@php
    $sectionLabel = $filters['section']?->getLabel();
    $heading = $activeType?->name ?? $sectionLabel ?? 'Documentos';
    $baseQuery = array_filter(['seccion' => $filters['section']?->value, 'tipo' => $filters['type'], 'q' => $filters['term']]);
    $chipClasses = fn (bool $active): string => $active
        ? 'bg-brand-600 text-white ring-brand-600'
        : 'bg-white text-slate-700 ring-slate-200 hover:ring-brand-300 hover:text-brand-800';
@endphp

<x-layouts.app :title="$heading" description="Resoluciones, documentos de gestión, planeamiento, CMN, boletines y demás documentos públicos.">
    <x-page-header :title="$heading" subtitle="Busque y descargue resoluciones, documentos de gestión, planes e informes publicados por la entidad."
        :breadcrumbs="$activeType || $sectionLabel ? ['Documentos' => route('documents.index'), $heading => null] : ['Documentos' => null]" />

    <div class="container-site mt-10 grid grid-cols-1 gap-8 lg:grid-cols-4">
        {{-- Filtros --}}
        <aside class="lg:col-span-1" aria-label="Filtros de documentos"
            x-data="{ open: window.matchMedia('(min-width: 1024px)').matches }"
            x-init="window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => open = event.matches)">
            <div class="card p-5 lg:sticky lg:top-24">
                <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="categorias-documentos"
                    class="flex w-full items-center justify-between text-sm font-bold tracking-wider text-slate-500 uppercase lg:pointer-events-none">
                    Categorías
                    <x-heroicon-o-chevron-down class="size-5 transition lg:hidden" ::class="open && 'rotate-180'" />
                </button>
                <div id="categorias-documentos" x-show="open" x-collapse class="mt-3">
                <a href="{{ route('documents.index') }}" @class(['mb-2 block rounded-lg px-3 py-2 text-sm font-semibold', 'bg-brand-600 text-white' => ! $filters['section'] && ! $filters['type'], 'text-slate-700 hover:bg-slate-100' => $filters['section'] || $filters['type']])>
                    Todos los documentos
                </a>
                @foreach (\App\Enums\DocumentSection::cases() as $section)
                    @continue(! $typesBySection->has($section->value))
                    <div class="mt-4">
                        <a href="{{ route('documents.index', ['seccion' => $section->value]) }}" @class(['block px-3 text-xs font-bold tracking-wide uppercase', 'text-brand-700' => $filters['section'] === $section, 'text-slate-500 hover:text-brand-700' => $filters['section'] !== $section])>
                            {{ $section->getLabel() }}
                        </a>
                        <ul class="mt-1.5 space-y-0.5">
                            @foreach ($typesBySection[$section->value] as $type)
                                <li>
                                    <a href="{{ route('documents.index', ['tipo' => $type->slug]) }}" @class(['flex items-center justify-between gap-2 rounded-lg px-3 py-1.5 text-sm', 'bg-brand-50 font-semibold text-brand-800' => $filters['type'] === $type->slug, 'text-slate-700 hover:bg-slate-100' => $filters['type'] !== $type->slug])>
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
            @if ($activeType)
                <div class="card mb-6 flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold tracking-wider text-brand-600 uppercase">{{ $activeType->section->getLabel() }}</p>
                        <h2 class="text-xl font-extrabold text-slate-900">{{ $activeType->name }}</h2>
                        @if ($activeType->description)
                            <p class="mt-1 text-sm text-slate-600">{{ $activeType->description }}</p>
                        @endif
                    </div>
                    <dl class="flex shrink-0 gap-6 text-center">
                        <div>
                            <dt class="text-xs text-slate-500">Documentos</dt>
                            <dd class="font-display text-2xl font-extrabold text-brand-700">{{ number_format($yearCounts->sum()) }}</dd>
                        </div>
                        @if ($yearCounts->isNotEmpty())
                            <div>
                                <dt class="text-xs text-slate-500">Periodo</dt>
                                <dd class="font-display text-2xl font-extrabold text-brand-700">{{ $yearCounts->keys()->last() }}–{{ $yearCounts->keys()->first() }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            <form method="GET" action="{{ route('documents.index') }}" class="card mb-4 grid gap-3 p-4 sm:grid-cols-[1fr_auto]" role="search">
                @if ($filters['section'])<input type="hidden" name="seccion" value="{{ $filters['section']->value }}">@endif
                @if ($filters['type'])<input type="hidden" name="tipo" value="{{ $filters['type'] }}">@endif
                <div class="relative">
                    <label for="q" class="sr-only">Buscar documentos</label>
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-slate-400" />
                    <input type="search" id="q" name="q" value="{{ $filters['term'] }}" placeholder="Título, número o código…" class="form-input pl-10">
                </div>
                <button type="submit" class="btn-primary">Buscar</button>
            </form>

            @if ($yearCounts->isNotEmpty())
                <nav aria-label="Filtrar por año" class="mb-6">
                    <p class="mb-2 text-xs font-bold tracking-wider text-slate-500 uppercase">Año</p>
                    <ul class="flex flex-wrap gap-2">
                        <li>
                            <a href="{{ route('documents.index', [...$baseQuery, 'anio' => 'todos']) }}"
                                class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-semibold ring-1 transition {{ $chipClasses($filters['year'] === null) }}">
                                Todos
                                <span class="text-xs opacity-75">{{ $yearCounts->sum() }}</span>
                            </a>
                        </li>
                        @foreach ($yearCounts as $year => $total)
                            <li>
                                <a href="{{ route('documents.index', [...$baseQuery, 'anio' => $year]) }}" @if ($filters['year'] === (int) $year) aria-current="true" @endif
                                    class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-semibold ring-1 transition {{ $chipClasses($filters['year'] === (int) $year) }}">
                                    {{ $year }}
                                    <span class="text-xs opacity-75">{{ $total }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            <p class="mb-3 text-sm text-slate-500" aria-live="polite">
                {{ $documents->total() }} {{ \Illuminate\Support\Str::plural('documento', $documents->total()) }}
                @if ($filters['year']) del año <strong class="text-slate-700">{{ $filters['year'] }}</strong>@endif
                @if ($filters['term']) para «{{ $filters['term'] }}»@endif
            </p>

            @if ($documents->isNotEmpty())
                <ul class="card divide-y divide-slate-100 overflow-hidden">
                    @foreach ($documents as $document)
                        <x-document-item :document="$document" />
                    @endforeach
                </ul>
                <div class="mt-8">{{ $documents->links() }}</div>
            @else
                <x-empty-state icon="heroicon-o-document-text" title="No se encontraron documentos" message="Ajuste los filtros o intente con otro término de búsqueda." />
            @endif
        </div>
    </div>
</x-layouts.app>
