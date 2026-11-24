{{-- Tarjeta de un tipo de documento en las portadas de Documentos e Información agraria: qué contiene, cuántos documentos tiene y desde cuándo. --}}
@props(['entry'])

@php
    /** @var \App\Support\Documents\DocumentTypeEntry $entry */
    $type = $entry->type;
    $iconTone = match ($type->section) {
        \App\Enums\DocumentSection::Normativa => 'bg-brand-50 text-brand-700 ring-brand-200/70 group-hover:bg-brand-600 group-hover:text-white group-hover:ring-brand-600',
        \App\Enums\DocumentSection::Gestion => 'bg-slate-100 text-slate-700 ring-slate-200 group-hover:bg-ink group-hover:text-white group-hover:ring-ink',
        \App\Enums\DocumentSection::InformacionAgraria => 'bg-gold-100 text-gold-600 ring-gold-300/50 group-hover:bg-gold-400 group-hover:text-brand-950 group-hover:ring-gold-400',
    };
    $yearRange = match (true) {
        $entry->firstYear === null => null,
        $entry->firstYear === $entry->lastYear => 'de '.$entry->firstYear,
        default => 'de '.$entry->firstYear.' a '.$entry->lastYear,
    };
@endphp

<article {{ $attributes->class('card card-link group relative flex h-full flex-col p-5 sm:p-6') }}>
    <div class="flex items-start gap-4">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl ring-1 transition-colors duration-300 ring-inset {{ $iconTone }}">
            <x-dynamic-component :component="$type->section->icon()" class="size-5" />
        </span>
        <div class="min-w-0">
            <h3 class="font-display text-lg leading-snug font-bold text-ink transition-colors group-hover:text-brand-700">
                <a href="{{ $type->publicUrl() }}"
                    class="after:absolute after:inset-0 after:rounded-2xl focus:outline-none focus-visible:after:outline-2 focus-visible:after:outline-offset-2 focus-visible:after:outline-gold-500">{{ $type->name }}</a>
            </h3>
            @if ($type->description)
                <p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ $type->description }}</p>
            @endif
            @if ($entry->latestPeriod)
                <p class="mt-3 text-sm text-slate-600">Último reporte: <strong class="font-semibold text-ink">{{ $entry->latestPeriod->translatedFormat('F Y') }}</strong></p>
            @endif
        </div>
    </div>

    @if (count($entry->years) > 1)
        {{-- Sobre el enlace de toda la tarjeta (relative z-10): cada año abre el tema en ese año. --}}
        <ul class="relative z-10 mt-4 flex flex-wrap gap-1.5 sm:pl-15" aria-label="Años de {{ $type->name }}">
            @foreach (array_slice($entry->years, 0, 4) as $year)
                <li>
                    <a href="{{ $type->publicUrl(['anio' => $year]) }}" class="inline-flex min-h-8 items-center rounded-full bg-slate-100 px-3 text-xs font-semibold text-slate-700 transition hover:bg-brand-600 hover:text-white">{{ $year }}</a>
                </li>
            @endforeach
            @if (count($entry->years) > 4)
                <li>
                    <a href="{{ $type->publicUrl(['anio' => 'todos']) }}" class="inline-flex min-h-8 items-center rounded-full px-2 text-xs font-semibold text-brand-700 hover:text-brand-900">
                        Todos los años<span class="sr-only"> de {{ $type->name }}</span>
                    </a>
                </li>
            @endif
        </ul>
    @endif

    <div class="mt-auto pt-5">
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-t border-slate-100 pt-4">
            <p class="text-sm text-slate-600">
                <strong class="font-semibold text-ink">{{ number_format($entry->total) }} {{ $entry->total === 1 ? 'documento' : 'documentos' }}</strong>
                @if ($yearRange)
                    {{ $yearRange }}
                @endif
            </p>
            @if ($entry->lastPublishedOn)
                <p class="text-xs text-slate-500">
                    Actualizado el <time datetime="{{ $entry->lastPublishedOn->toDateString() }}">{{ $entry->lastPublishedOn->translatedFormat('j M Y') }}</time>
                </p>
            @endif
        </div>
    </div>
</article>
