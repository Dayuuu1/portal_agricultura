{{-- Un indicador estadístico del año: calendario enero–diciembre con los meses publicados, más las publicaciones sin mes. --}}
@props(['series', 'year'])

@php
    $monthLabels = \App\Support\Documents\DocumentSeries::monthLabels();
    $isCurrentYear = $year === now()->year;
@endphp

<section {{ $attributes->class('card p-5 sm:p-6') }} aria-labelledby="serie-{{ $series->key }}">
    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
        <h3 id="serie-{{ $series->key }}" class="font-display text-base font-bold text-ink sm:text-lg">{{ $series->name }}</h3>
        <p class="text-xs text-slate-500">{{ $series->count }} {{ $series->count === 1 ? 'publicación' : 'publicaciones' }}</p>
    </div>

    @if ($series->hasCalendar())
        <ol class="mt-4 grid grid-cols-6 gap-1.5 sm:grid-cols-12" aria-label="Meses de {{ $year }}">
            @foreach ($monthLabels as $month => $monthLabel)
                @php($document = $series->calendar->get($month))
                @if ($document)
                    <li>
                        <a href="{{ route('documents.show', $document) }}" title="{{ $document->title }}"
                            class="flex min-h-11 items-center justify-center rounded-lg bg-brand-50 text-sm font-semibold text-brand-800 ring-1 ring-brand-200 transition duration-200 ring-inset hover:-translate-y-px hover:bg-brand-600 hover:text-white hover:ring-brand-600 active:translate-y-0">
                            <span aria-hidden="true">{{ $monthLabel }}</span>
                            <span class="sr-only">{{ $document->title }}</span>
                        </a>
                    </li>
                @else
                    <li @class([
                        'flex min-h-11 items-center justify-center rounded-lg text-sm',
                        'border border-dashed border-slate-300 text-slate-500' => ! $isCurrentYear || $month < now()->month,
                        'text-slate-400' => $isCurrentYear && $month >= now()->month,
                    ])>
                        <span aria-hidden="true">{{ $monthLabel }}</span>
                        <span class="sr-only">{{ ucfirst(\Illuminate\Support\Carbon::create($year, $month)->translatedFormat('F')) }}: sin publicación</span>
                    </li>
                @endif
            @endforeach
        </ol>
    @endif

    @if ($series->others->isNotEmpty())
        <div class="mt-4">
            @if ($series->hasCalendar())
                <p class="mb-2 text-xs font-semibold text-slate-600">Otras publicaciones de este indicador</p>
            @endif
            <ul class="flex flex-wrap gap-1.5">
                @foreach ($series->others as $entry)
                    <li>
                        <a href="{{ route('documents.show', $entry['document']) }}" title="{{ $entry['document']->title }}"
                            class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-brand-800 ring-1 ring-brand-200 transition duration-200 ring-inset hover:-translate-y-px hover:bg-brand-600 hover:text-white hover:ring-brand-600">
                            <span aria-hidden="true">{{ $entry['label'] }}</span>
                            <span class="sr-only">{{ $entry['document']->title }}@if ($entry['document']->issued_at), publicado el {{ $entry['document']->issued_at->translatedFormat('j \d\e F \d\e Y') }}@endif</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($series->others->contains('isPublicationDate', true))
                <p class="mt-2 text-xs text-slate-500">Los títulos de estas publicaciones no indican el mes; se muestran por fecha de publicación.</p>
            @endif
        </div>
    @endif
</section>
