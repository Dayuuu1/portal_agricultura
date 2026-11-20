{{-- Scaled-down copy of the home page hero, fed with the form state (SlideResource::form). --}}
@php
    $eyebrow = filled($eyebrow) ? $eyebrow : (\App\Models\Setting::valueOf('site_acronym') ?? 'DRDAR').' · Madre de Dios';
@endphp

<div class="relative overflow-hidden rounded-xl text-white shadow-inner ring-1 ring-gray-950/10" style="background-color: #12412a; aspect-ratio: 16 / 6; container-type: inline-size;">
    @if ($background)
        <img src="{{ $background }}" alt="" class="absolute inset-0 h-full w-full object-cover">
    @endif
    <div class="absolute inset-0" style="{{ $overlay->gradientStyle() }}"></div>

    <div class="relative grid h-full items-center gap-[3cqw] px-[5cqw]" style="grid-template-columns: {{ $image ? '7fr 5fr' : '1fr' }};">
        <div>
            <p class="mb-[1.2cqw] inline-flex rounded-full px-[1cqw] py-[0.3cqw] font-semibold tracking-wider uppercase ring-1 ring-white/25" style="font-size: 1.1cqw; color: #f1c95f; background: rgb(255 255 255 / 0.1);">
                {{ $eyebrow }}
            </p>
            <p class="font-extrabold" style="font-size: 3.6cqw; line-height: 1.1;">{{ filled($title) ? $title : 'Título del banner' }}</p>
            @if (filled($subtitle))
                <p class="mt-[1.3cqw]" style="font-size: 1.5cqw; color: #d8f3df; max-width: 46cqw;">{{ $subtitle }}</p>
            @endif
            @if ($hasButton)
                <span class="mt-[2cqw] inline-flex rounded-lg font-semibold" style="font-size: 1.3cqw; padding: 0.9cqw 1.6cqw; background: #ebb43a; color: #082417;">{{ filled($buttonText) ? $buttonText : 'Ver más' }} →</span>
            @endif
        </div>
        @if ($image)
            <img src="{{ $image }}" alt="" class="w-full rounded-[1.5cqw] object-cover shadow-xl ring-1 ring-white/20" style="aspect-ratio: 4 / 3;">
        @endif
    </div>
</div>
@if (! $background)
    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Sin imagen de fondo se usa el fondo verde institucional. Suba una foto en «Imágenes» para un resultado más profesional.</p>
@endif
