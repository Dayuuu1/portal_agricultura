@props(['eyebrow' => null, 'title', 'link' => null, 'linkLabel' => 'Ver todo'])

<div {{ $attributes->merge(['class' => 'mb-8 flex flex-wrap items-end justify-between gap-4']) }}>
    <div>
        @if ($eyebrow)
            <p class="mb-1.5 text-sm font-semibold tracking-wider text-brand-600 uppercase">{{ $eyebrow }}</p>
        @endif
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ $title }}</h2>
    </div>
    @if ($link)
        <a href="{{ $link }}" class="group inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-900">
            {{ $linkLabel }}
            <x-heroicon-o-arrow-right class="size-4 transition group-hover:translate-x-0.5" />
        </a>
    @elseif ($slot->isNotEmpty())
        {{ $slot }}
    @endif
</div>
