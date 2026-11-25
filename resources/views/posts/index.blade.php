<x-layouts.app title="Noticias" :description="'Noticias, comunicados, campañas y ferias de la '.\App\Models\Setting::valueOf('site_name', config('app.name')).'.'">
    <x-page-header title="Noticias" subtitle="Actividades, campañas, ferias y comunicados de la Dirección Regional." :breadcrumbs="['Noticias' => null]" />
    <x-analytics-search :term="$term" :results="$posts->total()" scope="noticias" />

    <div class="container-site mt-10">
        <form method="GET" action="{{ route('posts.index') }}" class="card mb-8 flex flex-col gap-3 p-4 sm:flex-row sm:items-center" role="search">
            <label for="q" class="sr-only">Buscar noticias</label>
            <div class="relative flex-1">
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-slate-400" />
                <input type="search" id="q" name="q" value="{{ $term }}" placeholder="Buscar por título o contenido…" class="form-input pl-10">
            </div>
            @if ($activeCategory)
                <input type="hidden" name="categoria" value="{{ $activeCategory }}">
            @endif
            <button type="submit" class="btn-primary">Buscar</button>
        </form>

        <nav aria-label="Categorías" class="mb-8 flex flex-wrap gap-2">
            <a href="{{ route('posts.index', array_filter(['q' => $term])) }}"
                @class(['inline-flex min-h-10 items-center rounded-full px-4 py-2 text-sm font-semibold ring-1 transition', 'bg-brand-600 text-white ring-brand-600 shadow-sm shadow-brand-900/20' => ! $activeCategory, 'bg-white text-slate-700 ring-slate-200 hover:-translate-y-px hover:ring-brand-300' => $activeCategory])>
                Todas
            </a>
            @foreach ($categories as $category)
                <a href="{{ route('posts.index', array_filter(['categoria' => $category->slug, 'q' => $term])) }}"
                    @class(['inline-flex min-h-10 items-center rounded-full px-4 py-2 text-sm font-semibold ring-1 transition', 'bg-brand-600 text-white ring-brand-600 shadow-sm shadow-brand-900/20' => $activeCategory === $category->slug, 'bg-white text-slate-700 ring-slate-200 hover:-translate-y-px hover:ring-brand-300' => $activeCategory !== $category->slug])
                    @if ($activeCategory === $category->slug) aria-current="true" @endif>
                    {{ $category->name }}
                </a>
            @endforeach
        </nav>

        @if ($posts->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post-card :post="$post" />
                @endforeach
            </div>
            <div class="mt-10">{{ $posts->onEachSide(1)->links() }}</div>
        @else
            <x-empty-state icon="heroicon-o-newspaper" title="No se encontraron noticias" message="Pruebe con otros términos de búsqueda o quite el filtro de categoría." />
        @endif
    </div>
</x-layouts.app>
