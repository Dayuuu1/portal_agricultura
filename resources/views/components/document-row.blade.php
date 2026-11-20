{{-- Fila compacta de un documento dentro de una publicación agrupada: el nombre lleva a la ficha y el botón descarga. --}}
@props(['document', 'label' => null, 'withContext' => false])

<li {{ $attributes->class('group relative flex items-center gap-3 px-4 py-3 transition-colors duration-200 hover:bg-brand-50/60 sm:gap-4 sm:px-5') }}>
    <x-file-type-tile :extension="$document->file_extension" size="sm" />
    <div class="min-w-0 flex-1">
        <a href="{{ route('documents.show', $document) }}"
            class="font-semibold wrap-anywhere text-ink transition-colors after:absolute after:inset-0 group-hover:text-brand-700 focus:outline-none focus-visible:after:outline-2 focus-visible:after:-outline-offset-2 focus-visible:after:outline-gold-500">{{ $label ?? $document->title }}</a>
        @if ($withContext)
            <p class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-slate-500">
                <span>{{ $document->documentType?->name }}</span>
                @if ($document->issued_at)
                    <time datetime="{{ $document->issued_at->toDateString() }}">{{ $document->issued_at->translatedFormat('j M Y') }}</time>
                @endif
            </p>
        @elseif ($document->human_file_size)
            <p class="mt-0.5 text-xs text-slate-500">{{ $document->human_file_size }}</p>
        @endif
    </div>
    @if ($document->hasFile())
        <a href="{{ route('documents.download', $document) }}" title="Descargar"
            class="relative z-10 flex size-11 shrink-0 items-center justify-center rounded-xl text-brand-700 ring-1 ring-brand-200 transition duration-200 ring-inset hover:bg-brand-600 hover:text-white hover:ring-brand-600 active:scale-[0.96]">
            <x-heroicon-m-arrow-down-tray class="size-5" />
            <span class="sr-only">Descargar {{ $document->title }}</span>
        </a>
    @endif
</li>
