<x-layouts.app :title="$post->title" :description="$post->summary" :image="$post->cover_url">
    <x-page-header :title="$post->title" :breadcrumbs="['Noticias' => route('posts.index'), \Illuminate\Support\Str::limit($post->title, 40) => null]">
        <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-brand-100">
            @if ($post->category)
                <a href="{{ route('posts.index', ['categoria' => $post->category->slug]) }}" class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 font-semibold text-white hover:bg-white/25">
                    <x-heroicon-o-tag class="size-4" /> {{ $post->category->name }}
                </a>
            @endif
            <time datetime="{{ $post->published_at->toIso8601String() }}" class="inline-flex items-center gap-1.5">
                <x-heroicon-o-calendar-days class="size-4" /> {{ $post->published_at->translatedFormat('l j \d\e F \d\e Y') }}
            </time>
        </div>
    </x-page-header>

    <div class="container-site mt-10 grid gap-10 lg:grid-cols-3">
        <article class="lg:col-span-2">
            @if ($post->cover_url)
                <img src="{{ $post->cover_url }}" alt="{{ $post->title }}" class="mb-8 aspect-video w-full rounded-2xl object-cover shadow-sm">
            @endif

            <div class="card p-6 sm:p-10">
                @if ($post->excerpt)
                    <p class="mb-6 border-l-4 border-gold-400 pl-4 text-lg font-medium text-slate-700">{{ $post->excerpt }}</p>
                @endif
                @php($hasBody = filled(trim(strip_tags((string) $post->body, '<img>'))))
                @if ($hasBody)
                    <div class="prose prose-slate max-w-none prose-headings:font-display prose-a:text-brand-700">
                        {!! str($post->body)->sanitizeHtml() !!}
                    </div>
                @endif

                @if ($post->galleryUrls())
                    <h2 @class(['mb-4 text-lg font-bold', 'mt-10' => $hasBody || $post->excerpt])>Galería</h2>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($post->galleryUrls() as $imageUrl)
                            <a href="{{ $imageUrl }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl">
                                <img src="{{ $imageUrl }}" alt="Imagen {{ $loop->iteration }} de la galería" loading="lazy" class="aspect-square w-full object-cover transition hover:scale-105">
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3 text-sm">
                <span class="font-semibold text-slate-700">Compartir:</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-full bg-[#1877f2] px-3 py-1.5 font-semibold text-white">
                    <x-site.social-icon network="facebook" /> Facebook
                </a>
                <a href="https://wa.me/?text={{ urlencode($post->title.' '.url()->current()) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-full bg-[#25d366] px-3 py-1.5 font-semibold text-white">
                    WhatsApp
                </a>
            </div>
        </article>

        <aside class="space-y-6">
            <div class="card p-6">
                <h2 class="mb-4 font-bold text-slate-900">Noticias relacionadas</h2>
                <ul class="space-y-4">
                    @forelse ($related as $item)
                        <li>
                            <a href="{{ route('posts.show', $item) }}" class="group block">
                                <span class="text-xs font-medium text-slate-500">{{ $item->published_at->translatedFormat('j M Y') }}</span>
                                <span class="block text-sm font-semibold text-slate-800 group-hover:text-brand-700">{{ $item->title }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No hay otras noticias en esta categoría.</li>
                    @endforelse
                </ul>
            </div>
            <a href="{{ route('posts.index') }}" class="btn-secondary w-full"><x-heroicon-o-chevron-left class="size-4" /> Volver a noticias</a>
        </aside>
    </div>
</x-layouts.app>
