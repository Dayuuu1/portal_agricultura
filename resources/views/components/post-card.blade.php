@props(['post', 'large' => false])

<article {{ $attributes->merge(['class' => 'card group flex h-full flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md']) }}>
    <a href="{{ route('posts.show', $post) }}" class="relative block overflow-hidden" tabindex="-1" aria-hidden="true">
        @if ($post->cover_url)
            <img src="{{ $post->cover_url }}" alt="" loading="lazy" @class(['w-full object-cover transition duration-500 group-hover:scale-105', 'aspect-video' => $large, 'aspect-16/10' => ! $large])>
        @else
            <div @class(['flex w-full items-center justify-center bg-linear-to-br from-brand-600 to-brand-800', 'aspect-video' => $large, 'aspect-16/10' => ! $large])>
                <div class="bg-pattern absolute inset-0"></div>
                <x-heroicon-o-newspaper class="relative size-12 text-white/40" />
            </div>
        @endif
        @if ($post->category)
            <span class="absolute top-3 left-3 rounded-full bg-white/95 px-2.5 py-1 text-xs font-semibold text-brand-800 shadow-sm">{{ $post->category->name }}</span>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-5">
        <time datetime="{{ $post->published_at->toDateString() }}" class="mb-2 flex items-center gap-1.5 text-xs font-medium text-slate-500">
            <x-heroicon-o-calendar-days class="size-4" />
            {{ $post->published_at->translatedFormat('j \d\e F \d\e Y') }}
        </time>
        <h3 @class(['font-bold text-slate-900 group-hover:text-brand-700', 'text-xl sm:text-2xl' => $large, 'text-base' => ! $large])>
            <a href="{{ route('posts.show', $post) }}" class="focus:outline-none">{{ $post->title }}</a>
        </h3>
        <p @class(['mt-2 text-sm text-slate-600', 'line-clamp-3' => $large, 'line-clamp-2' => ! $large])>{{ $post->summary }}</p>
    </div>
</article>
