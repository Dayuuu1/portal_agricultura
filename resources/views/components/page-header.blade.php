@props(['title', 'subtitle' => null, 'breadcrumbs' => []])

<section class="relative isolate overflow-hidden bg-brand-900 text-white">
    <div class="absolute inset-0 -z-20 bg-[radial-gradient(ellipse_at_85%_0%,var(--color-brand-700),transparent_65%)]" aria-hidden="true"></div>
    <x-site.contours class="contours-animate absolute inset-y-0 right-0 -z-10 h-full w-full text-brand-300/30 mask-l-from-40% mask-l-to-95% lg:w-3/4" />

    <div class="container-site py-10 sm:py-14 lg:py-16">
        <nav aria-label="Ruta de navegación" class="enter-now mb-5">
            <ol class="flex flex-wrap items-center gap-1.5 text-sm text-brand-100/90">
                <li><a href="{{ route('home') }}" class="link-draw hover:text-white">Inicio</a></li>
                @foreach ($breadcrumbs as $label => $url)
                    <li aria-hidden="true"><x-heroicon-m-chevron-right class="size-4 text-brand-300/70" /></li>
                    <li>
                        @if ($url)
                            <a href="{{ $url }}" class="link-draw hover:text-white">{{ $label }}</a>
                        @else
                            <span aria-current="page" class="font-medium text-white">{{ $label }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
        <h1 class="enter-now max-w-4xl text-3xl font-bold wrap-anywhere sm:text-4xl sm:wrap-normal lg:text-5xl lg:leading-[1.05]" style="--d: 70ms">{{ $title }}</h1>
        @if ($subtitle)
            <p class="enter-now mt-4 max-w-3xl text-base text-brand-100 sm:text-lg" style="--d: 140ms">{{ $subtitle }}</p>
        @endif
        @if ($slot->isNotEmpty())
            <div class="enter-now" style="--d: 210ms">{{ $slot }}</div>
        @endif
    </div>
    <div class="h-1 bg-[linear-gradient(90deg,var(--color-gold-400)_0_6rem,transparent_6rem)] sm:bg-[linear-gradient(90deg,var(--color-gold-400)_0_10rem,transparent_10rem)]" aria-hidden="true"></div>
</section>
