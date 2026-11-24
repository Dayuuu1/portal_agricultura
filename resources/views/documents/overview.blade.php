@php
    $heading = $section?->getLabel() ?? 'Documentos';
    $subtitle = $section?->description() ?? 'Resoluciones, instrumentos de gestión y documentos de transparencia publicados por la Dirección Regional, ordenados por tipo.';
@endphp

<x-layouts.app :title="$heading" :description="$subtitle">
    <x-page-header :title="$heading" :subtitle="$subtitle"
        :breadcrumbs="$section ? ['Documentos' => route('documents.index'), $heading => null] : ['Documentos' => null]" />

    <div class="container-site mt-10">
        <form method="GET" action="{{ route('documents.index') }}" class="card grid gap-3 p-4 sm:grid-cols-[1fr_auto]" role="search">
            @if ($section)<input type="hidden" name="seccion" value="{{ $section->value }}">@endif
            <div class="relative">
                <label for="q" class="sr-only">Buscar documentos</label>
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-slate-400" />
                <input type="search" id="q" name="q" placeholder="Buscar por título, número o código…" class="form-input pl-10">
            </div>
            <button type="submit" class="btn-primary">Buscar</button>
        </form>

        <div class="mt-12 grid gap-12 lg:grid-cols-12 lg:gap-10">
            <div class="space-y-14 lg:col-span-8">
                @foreach (\App\Enums\DocumentSection::cases() as $documentSection)
                    @continue(! $typesBySection->has($documentSection->value))
                    <section aria-labelledby="seccion-{{ $documentSection->value }}">
                        <div class="mb-6 flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
                            <div class="max-w-xl">
                                <h2 id="seccion-{{ $documentSection->value }}" class="text-2xl font-bold text-ink">{{ $section ? 'Tipos de documento' : $documentSection->getLabel() }}</h2>
                                @unless ($section)
                                    <p class="mt-1 text-slate-600">{{ $documentSection->description() }}</p>
                                @endunless
                            </div>
                            <a href="{{ route('documents.index', ['seccion' => $documentSection->value, 'vista' => 'lista']) }}" class="group inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-900">
                                <span class="link-draw">Ver como lista</span>
                                <x-heroicon-m-arrow-right class="size-4 transition duration-300 ease-out-quint group-hover:translate-x-1" />
                            </a>
                        </div>
                        <ul class="grid gap-4 sm:grid-cols-2">
                            @foreach ($typesBySection[$documentSection->value] as $entry)
                                <li><x-document-type-card :entry="$entry" /></li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach

                @if ($agrarian)
                    {{-- La estadística agraria tiene su propia portada: aquí solo se indica dónde está. --}}
                    <section aria-labelledby="seccion-informacion-agraria" class="relative isolate overflow-hidden rounded-3xl bg-brand-900 p-6 text-white shadow-lift sm:p-8">
                        <x-site.contours class="absolute inset-0 -z-10 size-full text-gold-300/15" />
                        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                            <div class="max-w-md">
                                <h2 id="seccion-informacion-agraria" class="text-2xl font-bold">{{ \App\Enums\DocumentSection::InformacionAgraria->getLabel() }}</h2>
                                <p class="mt-2 text-brand-100">
                                    Boletines y estadística agrícola, pecuaria y agroindustrial tienen su propia sección:
                                    {{ number_format($agrarian['total']) }} {{ $agrarian['total'] === 1 ? 'reporte' : 'reportes' }}{{ $agrarian['lastPublishedOn'] ? '; el último se publicó el '.$agrarian['lastPublishedOn']->translatedFormat('j M Y') : '' }}.
                                </p>
                            </div>
                            <a href="{{ route('agrarian-information') }}" class="btn-gold group shrink-0 px-5 py-3">
                                <x-heroicon-o-presentation-chart-line class="size-5" /> Ir a Información agraria
                            </a>
                        </div>
                    </section>
                @endif

                @if ($typesBySection->isEmpty() && ! $agrarian)
                    <x-empty-state icon="heroicon-o-document-text" title="Aún no hay documentos publicados" message="Cuando la entidad publique documentos aparecerán aquí, ordenados por tipo." />
                @endif
            </div>

            @if ($recent->isNotEmpty())
                <aside class="lg:col-span-4" aria-labelledby="publicados-recientemente">
                    <div class="lg:sticky lg:top-24">
                        <h2 id="publicados-recientemente" class="font-display text-xl font-bold text-ink">Publicados recientemente</h2>
                        <ul class="card mt-4 divide-y divide-slate-100 overflow-hidden">
                            @foreach ($recent as $document)
                                <x-document-row :document="$document" with-context />
                            @endforeach
                        </ul>
                        <a href="{{ route('documents.index', array_filter(['seccion' => $section?->value, 'vista' => 'lista'])) }}" class="btn-secondary mt-4 w-full">
                            Ver los {{ number_format($total) }} documentos en lista
                        </a>
                    </div>
                </aside>
            @endif
        </div>
    </div>
</x-layouts.app>
