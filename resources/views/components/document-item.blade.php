@props(['document', 'compact' => false])

<li {{ $attributes->merge(['class' => 'group flex gap-4 p-4 sm:p-5 transition hover:bg-brand-50/50']) }}>
    <span class="flex size-11 shrink-0 flex-col items-center justify-center rounded-lg bg-red-50 text-red-700 ring-1 ring-red-100">
        <x-heroicon-o-document-text class="size-5" />
        <span class="text-[0.6rem] font-bold">{{ $document->file_extension ?? 'DOC' }}</span>
    </span>
    <div class="min-w-0 flex-1">
        <h3 class="font-semibold wrap-anywhere text-slate-900 group-hover:text-brand-700">
            <a href="{{ route('documents.show', $document) }}">{{ $document->title }}</a>
        </h3>
        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
            @if (! $compact && $document->documentType)
                <span class="rounded-full bg-brand-50 px-2 py-0.5 font-medium text-brand-700">{{ $document->documentType->name }}</span>
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
        <a href="{{ route('documents.download', $document) }}" class="hidden shrink-0 items-center gap-1.5 self-center rounded-lg px-3 py-2 text-sm font-semibold text-brand-700 ring-1 ring-brand-200 hover:bg-brand-600 hover:text-white sm:inline-flex"
            aria-label="Descargar {{ $document->title }}">
            <x-heroicon-o-arrow-down-tray class="size-4" /> Descargar
        </a>
    @endif
</li>
