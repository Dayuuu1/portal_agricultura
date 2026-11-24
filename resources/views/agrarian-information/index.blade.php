<x-layouts.app title="Información agraria" description="Estadística agrícola, pecuaria y agroindustrial de Madre de Dios: boletines, producción, precios e insumos publicados cada mes por la Dirección Regional." nav="informacion">
    <x-page-header title="Información agraria" subtitle="Estadística agrícola, pecuaria y agroindustrial de Madre de Dios, publicada cada mes por la Dirección Regional." :breadcrumbs="['Información agraria' => null]">
        @if ($total > 0)
            <p class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-brand-100">
                <span class="inline-flex items-center gap-1.5">
                    <x-heroicon-o-document-chart-bar class="size-4" />
                    {{ number_format($total) }} {{ $total === 1 ? 'reporte' : 'reportes' }}{{ $firstYear ? ' desde '.$firstYear : '' }}
                </span>
                @if ($latestDay)
                    <span class="inline-flex items-center gap-1.5">
                        <x-heroicon-o-calendar-days class="size-4" />
                        Última actualización: <time datetime="{{ $latestDay->toDateString() }}">{{ $latestDay->translatedFormat('j \d\e F \d\e Y') }}</time>
                    </span>
                @endif
            </p>
        @endif
    </x-page-header>

    <div class="container-site mt-10 space-y-16">
        <form method="GET" action="{{ route('documents.index') }}" class="card grid gap-3 p-4 sm:grid-cols-[1fr_auto]" role="search">
            <input type="hidden" name="seccion" value="{{ \App\Enums\DocumentSection::InformacionAgraria->value }}">
            <input type="hidden" name="vista" value="lista">
            <div class="relative">
                <label for="q" class="sr-only">Buscar en información agraria</label>
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-slate-400" />
                <input type="search" id="q" name="q" placeholder="Buscar un reporte. Ej.: precio en chacra, beneficio de ganado, molinos…" class="form-input pl-10">
            </div>
            <button type="submit" class="btn-primary">Buscar</button>
        </form>

        @if ($topics->isNotEmpty())
            <section aria-labelledby="temas">
                <div class="mb-6 max-w-2xl">
                    <h2 id="temas" class="text-2xl font-bold text-ink sm:text-[2rem] sm:leading-tight">Temas</h2>
                    <p class="mt-1 text-slate-600">Cada tema reúne sus reportes ordenados por año y por el mes que informan.</p>
                </div>
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($topics as $entry)
                        <li><x-document-type-card :entry="$entry" /></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($latestRelease->isNotEmpty())
            <section aria-labelledby="ultima-publicacion">
                <div class="mb-6 max-w-2xl">
                    <h2 id="ultima-publicacion" class="text-2xl font-bold text-ink sm:text-[2rem] sm:leading-tight">Lo último publicado</h2>
                    <p class="mt-1 text-slate-600">
                        Reportes publicados el <time datetime="{{ $latestDay->toDateString() }}">{{ $latestDay->translatedFormat('j \d\e F \d\e Y') }}</time>.
                        El título de cada uno indica el mes al que corresponden sus datos.
                    </p>
                </div>
                <div class="grid items-start gap-5 md:grid-cols-2">
                    @foreach ($latestRelease as $group)
                        <section class="card overflow-hidden" aria-labelledby="ultimo-{{ $group['type']->slug }}">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 px-5 pt-5 sm:px-6">
                                <h3 id="ultimo-{{ $group['type']->slug }}" class="font-display text-lg font-bold text-ink">{{ $group['type']->name }}</h3>
                                <a href="{{ $group['type']->publicUrl() }}" class="group inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-900">
                                    <span class="link-draw">{{ $group['total'] > $group['documents']->count() ? 'Ver los '.$group['total'].' reportes' : 'Ver el tema' }}</span>
                                    <x-heroicon-m-arrow-right class="size-4 transition duration-300 ease-out-quint group-hover:translate-x-1" />
                                    <span class="sr-only">{{ $group['type']->name }}</span>
                                </a>
                            </div>
                            <ul class="mt-2 divide-y divide-slate-100 border-t border-slate-100">
                                @foreach ($group['documents'] as $document)
                                    <x-document-row :document="$document" />
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($archived->isNotEmpty())
            <section aria-labelledby="archivo">
                <div class="mb-6 max-w-2xl">
                    <h2 id="archivo" class="flex items-center gap-2 text-xl font-bold text-ink">
                        <x-heroicon-o-archive-box class="size-5 text-slate-500" /> Series sin publicaciones recientes
                    </h2>
                    <p class="mt-1 text-slate-600">No se han actualizado en los últimos dos años. Se conservan para consulta.</p>
                </div>
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($archived as $entry)
                        <li><x-document-type-card :entry="$entry" /></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($topics->isEmpty() && $archived->isEmpty())
            <x-empty-state icon="heroicon-o-chart-bar" title="No hay información agraria publicada" message="Cuando la Dirección Regional publique sus reportes estadísticos aparecerán aquí, ordenados por tema." />
        @endif

        <p class="flex items-start gap-2 text-sm text-slate-600">
            <x-heroicon-o-information-circle class="mt-0.5 size-5 shrink-0 text-brand-600" />
            <span>
                ¿Busca resoluciones, el TUPA, el POI u otros documentos de la entidad? Están en
                <a href="{{ route('documents.index') }}" class="font-semibold text-brand-700 underline decoration-brand-300 underline-offset-4 hover:decoration-brand-700">Documentos</a>.
            </span>
        </p>
    </div>
</x-layouts.app>
