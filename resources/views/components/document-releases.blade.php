{{-- Línea de tiempo de publicaciones estadísticas: un bloque por mes reportado (o por día de publicación en los años sin mes en el título). --}}
@props(['releases'])

<ol {{ $attributes->class('relative space-y-8 sm:before:absolute sm:before:top-4 sm:before:bottom-4 sm:before:left-10 sm:before:w-px sm:before:bg-slate-300/70') }}>
    @foreach ($releases as $release)
        <li class="relative grid gap-3 sm:grid-cols-[5rem_minmax(0,1fr)] sm:gap-6">
            <div class="hidden sm:block" aria-hidden="true">
                <p class="sticky top-24 flex flex-col items-center rounded-2xl bg-white px-2 py-2.5 text-center shadow-sm ring-1 ring-slate-900/[0.07]">
                    <span class="font-display text-xl leading-none font-extrabold text-brand-800">{{ $release->badgeTop }}</span>
                    <span class="mt-1 text-xs font-semibold whitespace-nowrap text-slate-500">{{ $release->badgeBottom }}</span>
                </p>
            </div>

            <section aria-labelledby="publicacion-{{ $release->key }}">
                <div class="mb-3 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5">
                    <h3 id="publicacion-{{ $release->key }}" class="font-display text-lg font-bold text-ink">{{ $release->heading }}</h3>
                    @if ($release->isReportingPeriod && $release->publishedOn)
                        <p class="text-xs text-slate-500">
                            Publicado el <time datetime="{{ $release->publishedOn->toDateString() }}">{{ $release->publishedOn->translatedFormat('j \d\e F \d\e Y') }}</time>
                        </p>
                    @endif
                </div>
                <ul class="card divide-y divide-slate-100 overflow-hidden">
                    @foreach ($release->entries as $entry)
                        <x-document-row :document="$entry['document']" :label="$entry['label']" />
                    @endforeach
                </ul>
            </section>
        </li>
    @endforeach
</ol>
