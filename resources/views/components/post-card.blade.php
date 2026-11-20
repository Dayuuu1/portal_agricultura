@props(['post', 'large' => false])

{{-- Toda la tarjeta es clicable (el enlace del título se extiende con ::after); la foto se acerca al pasar el puntero. --}}
<article {{ $attributes->merge(['class' => 'card card-link group relative flex h-full flex-col overflow-hidden']) }}>
    <div @class(['media', 'aspect-video' => $large, 'aspect-16/10' => ! $large])>
        @if ($post->cover_url)
            <img src="{{ $post->cover_url }}" alt="" loading="lazy" decoding="async">
        @endif
        @if ($post->category)
            <span class="absolute top-3 left-3 z-10 rounded-full bg-white/95 px-2.5 py-1 text-xs font-semibold text-brand-800 shadow-sm backdrop-blur-sm">{{ $post->category->name }}</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-5">
        <time datetime="{{ $post->published_at->toDateString() }}" class="mb-2 text-xs font-medium text-slate-500">
            {{ $post->published_at->translatedFormat('j \d\e F \d\e Y') }}
        </time>
        <h3 @class(['font-bold text-ink transition-colors group-hover:text-brand-700', 'text-xl sm:text-2xl' => $large, 'text-base leading-snug' => ! $large])>
            <a href="{{ route('posts.show', $post) }}" class="after:absolute after:inset-0 after:z-10 focus:outline-none focus-visible:after:rounded-2xl focus-visible:after:outline-2 focus-visible:after:outline-offset-2 focus-visible:after:outline-gold-500">{{ $post->title }}</a>
        </h3>
        @if ($post->summary)
            <p @class(['mt-2 text-sm text-slate-600', 'line-clamp-3' => $large, 'line-clamp-2' => ! $large])>{{ $post->summary }}</p>
        @endif
        <span class="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-semibold text-brand-700" aria-hidden="true">
            Leer noticia <x-heroicon-m-arrow-right class="size-4 transition duration-300 ease-out-quint group-hover:translate-x-1" />
        </span>
    </div>
</article>
