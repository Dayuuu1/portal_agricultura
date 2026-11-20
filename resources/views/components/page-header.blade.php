@props(['title', 'subtitle' => null, 'breadcrumbs' => []])

<section class="relative overflow-hidden bg-linear-to-br from-brand-800 via-brand-700 to-brand-600 text-white">
    <div class="bg-pattern absolute inset-0" aria-hidden="true"></div>
    <div class="absolute -right-20 -bottom-24 size-80 rounded-full bg-gold-400/15 blur-2xl" aria-hidden="true"></div>
    <div class="container-site relative py-10 sm:py-14">
        <nav aria-label="Ruta de navegación" class="mb-4">
            <ol class="flex flex-wrap items-center gap-1.5 text-sm text-brand-100">
                <li><a href="{{ route('home') }}" class="hover:text-white hover:underline">Inicio</a></li>
                @foreach ($breadcrumbs as $label => $url)
                    <li aria-hidden="true"><x-heroicon-o-chevron-right class="size-3.5" /></li>
                    <li>
                        @if ($url)
                            <a href="{{ $url }}" class="hover:text-white hover:underline">{{ $label }}</a>
                        @else
                            <span aria-current="page" class="text-white">{{ $label }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
        <h1 class="max-w-4xl text-3xl font-extrabold tracking-tight text-balance wrap-anywhere sm:text-4xl sm:wrap-normal">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-3 max-w-3xl text-base text-brand-100 sm:text-lg">{{ $subtitle }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
