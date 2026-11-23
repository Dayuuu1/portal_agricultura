{{--
    Foto de quien está a cargo de una unidad. Sin foto muestra sus iniciales (sin el grado: «Ing.», «Abog.»);
    sin nombre, una silueta. Las iniciales quedan debajo de la foto, así que también aparecen si el archivo no carga.
    El nombre siempre va escrito al lado, por eso la imagen no repite un texto alternativo.
--}}
@props(['src' => null, 'name' => null])

@php
    $initials = collect(preg_split('/\s+/u', trim((string) $name)) ?: [])
        ->reject(fn (string $word): bool => $word === '' || str_ends_with($word, '.'))
        ->take(2)
        ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
@endphp

<span {{ $attributes->merge(['class' => 'relative isolate flex shrink-0 items-center justify-center overflow-hidden bg-brand-100 font-display font-bold text-brand-800 ring-1 ring-brand-900/10']) }}>
    @if ($initials !== '')
        <span aria-hidden="true">{{ $initials }}</span>
    @else
        <x-heroicon-o-user class="size-1/2 text-brand-600" aria-hidden="true" />
    @endif
    @if ($src)
        <img src="{{ $src }}" alt="" class="absolute inset-0 size-full object-cover" loading="lazy" decoding="async">
    @endif
</span>
