@props(['eyebrow' => null, 'title', 'link' => null, 'linkLabel' => 'Ver todo'])

<div {{ $attributes->merge(['class' => 'mb-8 flex flex-wrap items-end justify-between gap-4']) }}>
    <div>
        @if ($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h2 class="text-2xl font-bold text-ink sm:text-[2rem] sm:leading-tight">{{ $title }}</h2>
    </div>
    @if ($link)
        <a href="{{ $link }}" class="group inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-900">
            <span class="link-draw">{{ $linkLabel }}</span>
            <x-heroicon-m-arrow-right class="size-4 transition duration-300 ease-out-quint group-hover:translate-x-1" />
        </a>
    @elseif ($slot->isNotEmpty())
        {{ $slot }}
    @endif
</div>
