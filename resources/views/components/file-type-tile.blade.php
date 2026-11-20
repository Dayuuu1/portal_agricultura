{{-- Ficha de color según el formato del archivo (PDF, Word, Excel, comprimido). --}}
@props(['extension' => null, 'size' => 'md'])

@php
    $extension = $extension ?: 'DOC';
    $tone = match ($extension) {
        'PDF' => 'bg-red-50 text-red-700 ring-red-200/70',
        'DOC', 'DOCX' => 'bg-sky-50 text-sky-700 ring-sky-200/70',
        'XLS', 'XLSX', 'CSV' => 'bg-emerald-50 text-emerald-700 ring-emerald-200/70',
        'ZIP', 'RAR' => 'bg-amber-50 text-amber-700 ring-amber-200/70',
        default => 'bg-slate-50 text-slate-600 ring-slate-200',
    };
@endphp

<span {{ $attributes->class([
    'flex shrink-0 flex-col items-center justify-center gap-0.5 ring-1 ring-inset',
    $tone,
    'size-12 rounded-xl' => $size === 'md',
    'size-10 rounded-lg' => $size === 'sm',
]) }}>
    <x-heroicon-o-document-text @class(['size-5' => $size === 'md', 'size-4' => $size === 'sm']) />
    <span class="text-[0.6rem] leading-none font-bold tracking-wide">{{ $extension }}</span>
</span>
