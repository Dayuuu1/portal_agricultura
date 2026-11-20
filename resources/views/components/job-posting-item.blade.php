@props(['jobPosting'])

@php($isOpen = $jobPosting->status === \App\Enums\JobPostingStatus::EnCurso)

<article {{ $attributes->merge(['class' => 'card card-link group relative flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="min-w-0">
        <div class="mb-2 flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $jobPosting->status->badgeClasses() }}">
                <span @class(['status-dot', 'is-live' => $isOpen]) aria-hidden="true"></span>
                {{ $jobPosting->status->getLabel() }}
            </span>
            @if ($jobPosting->modality)
                <span class="text-xs font-medium text-slate-500">{{ $jobPosting->modality }}</span>
            @endif
        </div>
        <h3 class="font-bold wrap-anywhere text-ink transition-colors group-hover:text-brand-700">
            <a href="{{ route('job-postings.show', $jobPosting) }}" class="after:absolute after:inset-0 after:rounded-2xl focus:outline-none focus-visible:after:outline-2 focus-visible:after:outline-offset-2 focus-visible:after:outline-gold-500">{{ $jobPosting->code }}</a>
        </h3>
        @if ($jobPosting->title !== $jobPosting->code)
            <p class="mt-0.5 line-clamp-2 text-sm text-slate-600">{{ $jobPosting->title }}</p>
        @endif
    </div>
    <div class="flex shrink-0 items-center justify-between gap-4 text-sm text-slate-500 sm:justify-end">
        @if ($jobPosting->starts_at)
            <span class="flex items-center gap-1.5 tabular-nums">
                <x-heroicon-o-calendar-days class="size-4" />
                {{ $jobPosting->starts_at->format('d/m/Y') }}@if ($jobPosting->ends_at) – {{ $jobPosting->ends_at->format('d/m/Y') }}@endif
            </span>
        @endif
        <span class="flex size-9 items-center justify-center rounded-full bg-brand-50 text-brand-700 transition duration-300 ease-out-quint group-hover:translate-x-0.5 group-hover:bg-brand-600 group-hover:text-white" aria-hidden="true">
            <x-heroicon-m-arrow-right class="size-4" />
        </span>
    </div>
</article>
