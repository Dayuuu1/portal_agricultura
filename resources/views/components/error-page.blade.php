@props(['code', 'title', 'message', 'search' => true])

<section class="relative isolate overflow-hidden">
    <x-site.contours class="contours-animate absolute inset-0 -z-10 size-full text-brand-300/50 mask-radial-from-20% mask-radial-to-75%" />
    <div class="container-site flex flex-col items-center py-20 text-center sm:py-28">
        <p class="enter-now font-display text-8xl leading-none font-extrabold text-brand-200 sm:text-9xl" aria-hidden="true">{{ $code }}</p>
        <h1 class="enter-now mt-6 text-3xl font-bold text-ink sm:text-4xl" style="--d: 80ms">{{ $title }}</h1>
        <p class="enter-now mt-4 max-w-lg text-lg text-slate-600" style="--d: 160ms">{{ $message }}</p>
        @if ($search)
            <form method="GET" action="{{ route('search') }}" class="enter-now mt-9 flex w-full max-w-md gap-2" role="search" style="--d: 240ms">
                <label for="busqueda-error" class="sr-only">Buscar en el portal</label>
                <input type="search" id="busqueda-error" name="q" minlength="3" placeholder="¿Qué está buscando?" class="form-input">
                <button type="submit" class="btn-primary">Buscar</button>
            </form>
        @endif
        <div class="enter-now mt-6 flex flex-wrap justify-center gap-3" style="--d: 300ms">
            {{ $slot }}
            <a href="{{ route('home') }}" class="btn-secondary"><x-heroicon-m-arrow-left class="size-4" /> Volver al inicio</a>
        </div>
    </div>
</section>
