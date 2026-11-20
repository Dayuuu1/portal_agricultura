<x-layouts.app :title="$post->title" :description="$post->summary" :image="$post->cover_url">
    <div class="reading-progress fixed inset-x-0 top-0 z-50 h-1 bg-gold-400" aria-hidden="true"></div>

    <x-page-header :title="$post->title" :breadcrumbs="['Noticias' => route('posts.index'), \Illuminate\Support\Str::limit($post->title, 40) => null]">
        <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-brand-100">
            @if ($post->category)
                <a href="{{ route('posts.index', ['categoria' => $post->category->slug]) }}" class="inline-flex min-h-9 items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/25">
                    <x-heroicon-o-tag class="size-4" /> {{ $post->category->name }}
                </a>
            @endif
            <time datetime="{{ $post->published_at->toIso8601String() }}" class="inline-flex items-center gap-1.5">
                <x-heroicon-o-calendar-days class="size-4" /> {{ ucfirst($post->published_at->translatedFormat('l j \d\e F \d\e Y')) }}
            </time>
        </div>
    </x-page-header>

    <div class="container-site mt-10 grid gap-10 lg:grid-cols-3">
        <article class="lg:col-span-2">
            @if ($post->cover_url)
                <figure class="media mb-8 aspect-video rounded-3xl shadow-lift">
                    <img src="{{ $post->cover_url }}" alt="{{ $post->title }}" fetchpriority="high">
                </figure>
            @endif

            <div class="card p-6 sm:p-10">
                @if ($post->excerpt)
                    <p class="mb-8 border-l-4 border-gold-400 pl-5 font-display text-xl leading-relaxed font-semibold text-ink">{{ $post->excerpt }}</p>
                @endif
                @php($hasBody = filled(trim(strip_tags((string) $post->body, '<img>'))))
                @if ($hasBody)
                    <div class="prose prose-lg max-w-none prose-slate prose-headings:font-display prose-headings:text-ink prose-a:text-brand-700 prose-a:decoration-brand-300 prose-a:underline-offset-4 hover:prose-a:decoration-brand-700 prose-img:rounded-2xl">
                        {!! str($post->body)->sanitizeHtml() !!}
                    </div>
                @endif

                @if ($post->galleryUrls())
                    <h2 @class(['mb-4 text-xl font-bold text-ink', 'mt-10' => $hasBody || $post->excerpt])>Galería</h2>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($post->galleryUrls() as $imageUrl)
                            <a href="{{ $imageUrl }}" target="_blank" rel="noopener" class="group media block aspect-square rounded-2xl" aria-label="Abrir la imagen {{ $loop->iteration }} de la galería en una pestaña nueva">
                                <img src="{{ $imageUrl }}" alt="Imagen {{ $loop->iteration }} de la galería" loading="lazy" decoding="async">
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-2.5 text-sm" x-data="{ copied: false }">
                <span class="mr-1 font-semibold text-slate-700">Compartir:</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-[#1463cc] px-4 py-1.5 font-semibold text-white transition hover:-translate-y-px hover:brightness-110 active:scale-[0.98]">
                    <x-site.social-icon network="facebook" /> Facebook
                </a>
                <a href="https://wa.me/?text={{ urlencode($post->title.' '.url()->current()) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-[#0b7a43] px-4 py-1.5 font-semibold text-white transition hover:-translate-y-px hover:brightness-110 active:scale-[0.98]">
                    WhatsApp
                </a>
                <button type="button" x-show="navigator.clipboard" x-cloak
                    @click="navigator.clipboard.writeText(@js(url()->current())).then(() => { copied = true; setTimeout(() => copied = false, 2500) })"
                    class="inline-flex min-h-10 items-center gap-1.5 rounded-full bg-white px-4 py-1.5 font-semibold text-brand-800 ring-1 ring-brand-200 transition ring-inset hover:-translate-y-px hover:bg-brand-50 active:scale-[0.98]">
                    <x-heroicon-o-link x-show="! copied" class="size-4" />
                    <x-heroicon-m-check x-show="copied" x-cloak class="size-4 text-brand-600" />
                    <span x-text="copied ? 'Enlace copiado' : 'Copiar enlace'">Copiar enlace</span>
                </button>
                <span class="sr-only" aria-live="polite" x-text="copied ? 'Enlace copiado al portapapeles' : ''"></span>
            </div>
        </article>

        <aside class="space-y-6 lg:sticky lg:top-20 lg:self-start">
            <div class="card p-6">
                <h2 class="mb-4 text-lg font-bold text-ink">Noticias relacionadas</h2>
                <ul class="-mx-2 space-y-1">
                    @forelse ($related as $item)
                        <li>
                            <a href="{{ route('posts.show', $item) }}" class="group block rounded-xl p-2 transition-colors hover:bg-brand-50">
                                <time datetime="{{ $item->published_at->toDateString() }}" class="text-xs font-medium text-slate-500">{{ $item->published_at->translatedFormat('j M Y') }}</time>
                                <span class="block text-sm leading-snug font-semibold text-slate-800 group-hover:text-brand-700">{{ $item->title }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="px-2 text-sm text-slate-500">No hay otras noticias en esta categoría.</li>
                    @endforelse
                </ul>
            </div>
            <a href="{{ route('posts.index') }}" class="btn-secondary w-full"><x-heroicon-m-chevron-left class="size-4" /> Volver a noticias</a>
        </aside>
    </div>
</x-layouts.app>
