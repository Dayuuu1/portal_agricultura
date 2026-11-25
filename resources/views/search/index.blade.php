{{-- Internal search results are not indexed (Google asks for it); the links they lead to are. --}}
<x-layouts.app title="Buscar" description="Buscador del portal: noticias, documentos, convocatorias y páginas." robots="noindex, follow">
    @if ($results !== null)
        <x-analytics-search :term="$term" :results="$total" scope="portal" />
    @endif
    <x-page-header title="Buscar en el portal" :breadcrumbs="['Buscar' => null]">
        <form method="GET" action="{{ route('search') }}" class="mt-6 flex max-w-2xl gap-2" role="search">
            <label for="q" class="sr-only">Término de búsqueda</label>
            <input type="search" id="q" name="q" value="{{ $term }}" minlength="3" required placeholder="Ej.: resolución, CAS 2026, TUPA, feria…" class="form-input py-3 text-base" autofocus>
            <button type="submit" class="btn-gold px-5"><x-heroicon-o-magnifying-glass class="size-5" /> Buscar</button>
        </form>
    </x-page-header>

    <div class="container-site mt-10">
        @if ($results === null)
            <x-empty-state icon="heroicon-o-magnifying-glass" title="Escriba al menos 3 caracteres" message="Busque noticias, resoluciones, documentos de gestión, convocatorias y páginas institucionales." />
        @elseif ($total === 0)
            <x-empty-state icon="heroicon-o-magnifying-glass" title="Sin resultados para «{{ $term }}»" message="Revise la ortografía o use términos más generales." />
        @else
            <p class="mb-6 text-slate-600" aria-live="polite">{{ $total }} resultados para <strong>«{{ $term }}»</strong></p>
            <div class="grid gap-8 lg:grid-cols-2">
                @if ($results['documents']->isNotEmpty())
                    <section class="lg:col-span-2">
                        <h2 class="mb-3 text-lg font-bold text-slate-900">Documentos</h2>
                        <ul class="card divide-y divide-slate-100 overflow-hidden">
                            @foreach ($results['documents'] as $document)
                                <x-document-item :document="$document" />
                            @endforeach
                        </ul>
                    </section>
                @endif
                @if ($results['posts']->isNotEmpty())
                    <section>
                        <h2 class="mb-3 text-lg font-bold text-slate-900">Noticias</h2>
                        <ul class="card divide-y divide-slate-100">
                            @foreach ($results['posts'] as $post)
                                <li class="p-4">
                                    <a href="{{ route('posts.show', $post) }}" class="font-semibold text-slate-900 hover:text-brand-700">{{ $post->title }}</a>
                                    <p class="text-xs text-slate-500">{{ $post->published_at->translatedFormat('j \d\e F \d\e Y') }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
                @if ($results['jobPostings']->isNotEmpty())
                    <section>
                        <h2 class="mb-3 text-lg font-bold text-slate-900">Convocatorias</h2>
                        <div class="space-y-3">
                            @foreach ($results['jobPostings'] as $jobPosting)
                                <x-job-posting-item :job-posting="$jobPosting" />
                            @endforeach
                        </div>
                    </section>
                @endif
                @if ($results['pages']->isNotEmpty())
                    <section>
                        <h2 class="mb-3 text-lg font-bold text-slate-900">Páginas</h2>
                        <ul class="card divide-y divide-slate-100">
                            @foreach ($results['pages'] as $page)
                                <li class="p-4"><a href="{{ route('pages.show', $page) }}" class="font-semibold text-slate-900 hover:text-brand-700">{{ $page->title }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        @endif
    </div>
</x-layouts.app>
