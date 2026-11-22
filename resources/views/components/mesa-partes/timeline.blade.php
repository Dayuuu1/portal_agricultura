{{-- Hoja de ruta: public steps of an expediente, oldest first. Notes are shown only where they speak to the citizen. --}}
@props(['movements', 'withNotes' => true])

@php
    $notesForCitizen = ['observado', 'subsanado', 'atendido', 'no-presentado', 'archivado'];
@endphp

<ol {{ $attributes->class('relative space-y-6 before:absolute before:top-2 before:bottom-2 before:left-[0.9375rem] before:w-px before:bg-slate-200') }}>
    @foreach ($movements as $movement)
        <li class="relative flex gap-4">
            <span @class([
                'relative z-10 mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full ring-4 ring-white',
                'bg-brand-600 text-white' => $loop->last,
                'bg-slate-100 text-slate-500' => ! $loop->last,
            ])>
                <x-dynamic-component :component="'heroicon-o-'.str($movement->to_status->getIcon()->value)->after('o-')" class="size-4" />
            </span>
            <div class="min-w-0 flex-1 pb-1">
                <p class="flex flex-wrap items-baseline gap-x-3 gap-y-0.5">
                    <span class="font-semibold text-ink">{{ $movement->actionLabel() }}</span>
                    <time datetime="{{ $movement->created_at->toIso8601String() }}" class="text-sm text-slate-500">{{ $movement->created_at->translatedFormat('j M Y, H:i') }}</time>
                </p>
                @if ($movement->unit)
                    <p class="mt-0.5 text-sm text-slate-600">{{ $movement->unit->name }}</p>
                @endif
                @if ($withNotes && $movement->note && in_array($movement->action, $notesForCitizen, true))
                    <p class="mt-2 rounded-xl bg-slate-50 px-4 py-3 text-sm whitespace-pre-line text-slate-700 ring-1 ring-slate-200">{{ $movement->note }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
