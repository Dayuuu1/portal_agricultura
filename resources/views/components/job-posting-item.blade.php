@props(['jobPosting'])

<article {{ $attributes->merge(['class' => 'card group flex flex-col gap-3 p-5 transition hover:shadow-md sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="min-w-0">
        <div class="mb-1.5 flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $jobPosting->status->badgeClasses() }}">
                {{ $jobPosting->status->getLabel() }}
            </span>
            <span class="text-xs font-medium text-slate-500">{{ $jobPosting->modality }}</span>
        </div>
        <h3 class="font-bold wrap-anywhere text-slate-900 group-hover:text-brand-700">
            <a href="{{ route('job-postings.show', $jobPosting) }}">{{ $jobPosting->code }}</a>
        </h3>
        <p class="text-sm text-slate-600">{{ $jobPosting->title }}</p>
    </div>
    <div class="flex shrink-0 items-center gap-4 text-sm text-slate-500">
        @if ($jobPosting->starts_at)
            <span class="flex items-center gap-1.5">
                <x-heroicon-o-calendar-days class="size-4" />
                {{ $jobPosting->starts_at->format('d/m/Y') }}@if ($jobPosting->ends_at) – {{ $jobPosting->ends_at->format('d/m/Y') }}@endif
            </span>
        @endif
        <a href="{{ route('job-postings.show', $jobPosting) }}" class="inline-flex items-center gap-1 font-semibold text-brand-700 hover:text-brand-900" aria-label="Ver detalle de {{ $jobPosting->code }}">
            Ver <x-heroicon-o-arrow-right class="size-4" />
        </a>
    </div>
</article>
