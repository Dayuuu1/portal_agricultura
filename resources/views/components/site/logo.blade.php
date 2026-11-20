@props(['inverse' => false])

{{-- Logo, parent entity and short name come from «Datos del portal» in the admin panel. --}}
<span {{ $attributes->merge(['class' => 'flex min-w-0 items-center gap-3']) }}>
    <img src="{{ \App\Models\Setting::logoUrl() }}" alt="" height="44" class="h-10 w-auto max-w-28 shrink-0 object-contain sm:h-11">

    <span class="min-w-0 leading-tight">
        @if (filled($parentEntity = \App\Models\Setting::valueOf('parent_entity')))
            <span @class(['block truncate text-[0.62rem] font-semibold tracking-wide uppercase sm:text-[0.68rem] sm:tracking-wider', 'text-brand-200' => $inverse, 'text-brand-600' => ! $inverse])>
                {{ $parentEntity }}
            </span>
        @endif
        <span @class(['block font-display text-[0.95rem] font-bold sm:text-base', 'text-white' => $inverse, 'text-brand-900' => ! $inverse])>
            {{ \App\Models\Setting::shortName() }}
        </span>
    </span>
</span>
