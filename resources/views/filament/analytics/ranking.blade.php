{{-- Ranked list with proportional bars (App\Filament\Widgets\Analytics\RankingWidget). --}}
<x-filament-widgets::widget>
    <x-filament::section :heading="$heading" :description="$description">
        @if ($rows === [])
            <div class="flex flex-col items-center gap-2 py-8 text-center">
                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-white/5">
                    <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedChartBar" class="h-5 w-5" />
                </span>
                <p class="max-w-xs text-sm text-gray-500 dark:text-gray-400">{{ $emptyMessage }}</p>
            </div>
        @else
            <div class="mb-1.5 flex items-center justify-between px-3 text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">
                <span>{{ $itemLabel }}</span>
                <span>{{ $valueLabel }}</span>
            </div>
            <ol class="space-y-1">
                @foreach ($rows as $row)
                    <li class="relative isolate overflow-hidden rounded-lg">
                        <span class="absolute inset-y-0 left-0 -z-10 rounded-lg bg-primary-50 dark:bg-primary-500/10" style="width: {{ $row['share'] }}%"></span>
                        <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                            <div class="min-w-0">
                                @if (filled($row['url'] ?? null))
                                    <a href="{{ $row['url'] }}" target="_blank" rel="noopener" class="block truncate font-medium text-gray-950 hover:text-primary-600 hover:underline dark:text-white" title="{{ $row['label'] }}">{{ $row['label'] }}</a>
                                @else
                                    <p class="truncate font-medium text-gray-950 dark:text-white" title="{{ $row['label'] }}">{{ $row['label'] }}</p>
                                @endif
                                @if (filled($row['hint'] ?? null))
                                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $row['hint'] }}</p>
                                @endif
                            </div>
                            <span class="shrink-0 font-semibold text-gray-700 tabular-nums dark:text-gray-200">{{ $row['display'] }}</span>
                        </div>
                    </li>
                @endforeach
            </ol>
            @if (count($rows) >= 10)
                <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">Se muestran los 10 primeros. La lista completa está en «Exportar informe» › Excel.</p>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
