@props(['inverse' => false])

<span {{ $attributes->merge(['class' => 'flex min-w-0 items-center gap-3']) }}>
    <img src="{{ asset('images/logo-emblema.png') }}" alt="" width="62" height="44" class="h-10 w-auto shrink-0 sm:h-11">

    <span class="min-w-0 leading-tight">
        <span @class(['block truncate text-[0.62rem] font-semibold tracking-wide uppercase sm:text-[0.68rem] sm:tracking-wider', 'text-brand-200' => $inverse, 'text-brand-600' => ! $inverse])>
            Gobierno Regional de Madre de Dios
        </span>
        <span @class(['block font-display text-[0.95rem] font-bold sm:text-base', 'text-white' => $inverse, 'text-brand-900' => ! $inverse])>
            Dirección Regional Agraria
        </span>
    </span>
</span>
