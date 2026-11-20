<x-layouts.app title="Página no encontrada">
    <section class="container-site flex flex-col items-center py-24 text-center">
        <p class="font-display text-7xl font-extrabold text-brand-200">404</p>
        <h1 class="mt-4 text-3xl font-extrabold text-slate-900">No encontramos esta página</h1>
        <p class="mt-3 max-w-lg text-slate-600">Es posible que el enlace haya cambiado con el nuevo portal. Pruebe con el buscador o vuelva al inicio.</p>
        <form method="GET" action="{{ route('search') }}" class="mt-8 flex w-full max-w-md gap-2" role="search">
            <label for="q404" class="sr-only">Buscar</label>
            <input type="search" id="q404" name="q" placeholder="¿Qué está buscando?" class="form-input">
            <button type="submit" class="btn-primary">Buscar</button>
        </form>
        <a href="{{ route('home') }}" class="mt-6 font-semibold text-brand-700 hover:underline">Volver al inicio</a>
    </section>
</x-layouts.app>
