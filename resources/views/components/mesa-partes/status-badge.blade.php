{{-- Status of an expediente with the portal palette; the label is always written out, never color alone. --}}
@props(['status', 'size' => 'md'])

@php
    $classes = match ($status->getColor()) {
        'info' => 'bg-sky-50 text-sky-800 ring-sky-200',
        'warning' => 'bg-amber-50 text-amber-800 ring-amber-300',
        'primary' => 'bg-brand-50 text-brand-800 ring-brand-200',
        'success' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'danger' => 'bg-red-50 text-red-800 ring-red-200',
        default => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
@endphp

<span {{ $attributes->class([
    'inline-flex shrink-0 items-center gap-1.5 rounded-full font-semibold ring-1 ring-inset',
    'px-2.5 py-1 text-xs' => $size === 'sm',
    'px-3 py-1 text-sm' => $size !== 'sm',
    $classes,
]) }}>
    <x-dynamic-component :component="'heroicon-o-'.str($status->getIcon()->value)->after('o-')" @class(['shrink-0', 'size-3.5' => $size === 'sm', 'size-4' => $size !== 'sm']) />
    {{ $status->getLabel() }}
</span>
