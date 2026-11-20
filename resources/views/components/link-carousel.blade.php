@props(['links', 'eyebrow' => null, 'title', 'headingId'])

@php
    /*
     * Infinite logo carousel. The track holds two identical halves and slides by -50%,
     * so it loops without a jump; each half repeats the links until it is wider than the screen.
     * Only the first occurrence of each link is exposed to keyboards and screen readers.
     */
    $repeat = max(1, (int) ceil(10 / max($links->count(), 1)));
    $items = collect(range(1, $repeat))->flatMap(fn (): iterable => $links)->values();
    $duration = max(30, $items->count() * 4);
@endphp

<div x-data="{ hovered: false, stopped: false }" class="link-carousel">
    <x-section-heading :eyebrow="$eyebrow" :title="$title" :id="$headingId">
        <button type="button" @click="stopped = ! stopped" :aria-pressed="stopped.toString()"
            class="link-carousel-toggle inline-flex min-h-11 items-center gap-1.5 rounded-full bg-white px-4 py-2 text-sm font-semibold text-brand-800 shadow-sm ring-1 ring-slate-200 transition hover:bg-brand-50 active:scale-[0.98]">
            <x-heroicon-o-pause x-show="! stopped" class="size-4" />
            <x-heroicon-o-play x-show="stopped" x-cloak class="size-4" />
            <span x-text="stopped ? 'Reanudar' : 'Pausar'">Pausar</span>
            <span class="sr-only">el desplazamiento de los enlaces</span>
        </button>
    </x-section-heading>

    <div class="link-carousel-viewport -my-2 py-2"
        @mouseenter="hovered = true" @mouseleave="hovered = false" @focusin="hovered = true" @focusout="hovered = false">
        <div class="link-carousel-track flex w-max" :class="(hovered || stopped) && 'is-paused'" style="--carousel-duration: {{ $duration }}s">
            @foreach ([false, true] as $isDuplicateHalf)
                <ul role="list" class="flex shrink-0 gap-4 pr-4" @if ($isDuplicateHalf) aria-hidden="true" data-duplicate @endif>
                    @foreach ($items as $link)
                        @php($isCopy = $isDuplicateHalf || $loop->index >= $links->count())
                        <li class="w-52 shrink-0 sm:w-60" @if ($isCopy && ! $isDuplicateHalf) aria-hidden="true" data-duplicate @endif>
                            <a href="{{ $link->url }}" target="_blank" rel="noopener" title="{{ $link->description ?: $link->name }}"
                                @if ($isCopy) tabindex="-1" @endif
                                class="card card-link group flex h-24 items-center justify-center px-5 py-3">
                                @if ($link->logo_url)
                                    <img src="{{ $link->logo_url }}" alt="{{ $link->description ? $link->name.': '.$link->description : $link->name }}"
                                        class="max-h-16 w-full object-contain grayscale-35 transition duration-300 group-hover:grayscale-0" loading="lazy" decoding="async">
                                @endif
                                {{-- Name shown when there is no logo, or when the logo file fails to load. --}}
                                <span @class(['text-center', 'link-logo-fallback' => $link->logo_url]) @if ($link->logo_url) aria-hidden="true" @endif>
                                    <span class="block font-display text-lg leading-tight font-bold text-brand-700">{{ $link->name }}</span>
                                    @if ($link->description)
                                        <span class="mt-0.5 line-clamp-2 block text-[0.7rem] leading-snug text-slate-500">{{ $link->description }}</span>
                                    @endif
                                </span>
                                <span class="sr-only">(se abre en una pestaña nueva)</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    </div>
</div>
