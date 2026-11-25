{{-- Ranked list with proportional bars for the printable statistics report (analytics/report.blade.php). --}}
@props(['title', 'rows', 'unit', 'empty' => 'Sin datos en el periodo.', 'note' => null])

@php
    $max = max([1, ...array_column($rows, 'value')]);
@endphp

<section {{ $attributes->merge(['class' => 'break-inside-avoid']) }}>
    <h3 class="mb-2 flex items-baseline justify-between gap-3 border-b border-slate-200 pb-1.5 text-sm font-bold text-ink">
        {{ $title }}
        <span class="text-[0.65rem] font-semibold tracking-wide text-slate-400 uppercase">{{ $unit }}</span>
    </h3>

    @if ($rows === [])
        <p class="py-3 text-xs text-slate-400 italic">{{ $empty }}</p>
    @else
        <ol class="space-y-1">
            @foreach ($rows as $row)
                <li class="relative isolate overflow-hidden rounded">
                    <span class="report-bar absolute inset-y-0 left-0 -z-10 rounded bg-brand-50" style="width: {{ round($row['value'] / $max * 100, 1) }}%"></span>
                    <div class="flex items-center justify-between gap-3 px-2 py-1 text-[0.72rem] leading-snug">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-800">{{ $row['label'] }}</p>
                            @if (filled($row['hint'] ?? null))
                                <p class="truncate text-[0.62rem] text-slate-500">{{ $row['hint'] }}</p>
                            @endif
                        </div>
                        <span class="shrink-0 font-semibold text-slate-700 tabular-nums">{{ $row['display'] ?? \App\Support\Analytics\SiteStatistics::formatNumber($row['value']) }}</span>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif

    @if ($note)
        <p class="mt-1.5 text-[0.62rem] text-slate-500">{{ $note }}</p>
    @endif
</section>
