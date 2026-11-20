@props(['document', 'compact' => false])

<li {{ $attributes->merge(['class' => 'group relative flex gap-4 p-4 transition-colors duration-200 hover:bg-brand-50/60 sm:p-5']) }}>
    <x-file-type-tile :extension="$document->file_extension" class="transition-transform duration-300 ease-out-quint group-hover:-translate-y-0.5" />
    <div class="min-w-0 flex-1">
        <h3 class="font-semibold wrap-anywhere text-ink transition-colors group-hover:text-brand-700">
            <a href="{{ route('documents.show', $document) }}" class="after:absolute after:inset-0 focus:outline-none focus-visible:after:outline-2 focus-visible:after:-outline-offset-2 focus-visible:after:outline-gold-500">{{ $document->title }}</a>
        </h3>
        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
            @if (! $compact && $document->documentType)
                <span class="rounded-full bg-brand-50 px-2 py-0.5 font-medium text-brand-700 ring-1 ring-brand-100 ring-inset">{{ $document->documentType->name }}</span>
            @endif
            @if ($document->code && $document->code !== $document->title)
                <span class="wrap-anywhere">{{ $document->code }}</span>
            @endif
            @if ($document->issued_at)
                <time datetime="{{ $document->issued_at->toDateString() }}">{{ $document->issued_at->translatedFormat('d/m/Y') }}</time>
            @endif
            @if ($document->human_file_size)
                <span>{{ $document->human_file_size }}</span>
            @endif
        </div>
    </div>
    @if ($document->hasFile())
        <a href="{{ route('documents.download', $document) }}"
            class="relative z-10 hidden min-h-10 shrink-0 items-center gap-1.5 self-center rounded-xl px-3 py-2 text-sm font-semibold text-brand-700 ring-1 ring-brand-200 transition duration-200 ring-inset hover:bg-brand-600 hover:text-white hover:ring-brand-600 active:scale-[0.98] sm:inline-flex"
            aria-label="Descargar {{ $document->title }}">
            <x-heroicon-m-arrow-down-tray class="size-4" /> Descargar
        </a>
    @endif
</li>
