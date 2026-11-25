{{-- App\Filament\Widgets\Analytics\LiveVisitors: refreshed every 30 seconds. --}}
<x-filament-widgets::widget>
    <div wire:poll.30s class="fi-section flex flex-wrap items-center gap-x-6 gap-y-3 rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex items-center gap-3">
            <span class="relative flex h-3 w-3" aria-hidden="true">
                @if ($visitors > 0)
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-400 opacity-60 motion-reduce:animate-none"></span>
                @endif
                <span @class(['relative inline-flex h-3 w-3 rounded-full', 'bg-success-500' => $visitors > 0, 'bg-gray-300 dark:bg-gray-600' => $visitors === 0])></span>
            </span>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                <span class="text-2xl font-bold text-gray-950 tabular-nums dark:text-white">{{ $visitors }}</span>
                {{ $visitors === 1 ? 'visitante' : 'visitantes' }} en los últimos {{ $minutes }} minutos
            </p>
        </div>

        @if ($sections !== [])
            <ul class="flex flex-wrap gap-2" aria-label="Secciones que están viendo">
                @foreach ($sections as $section => $count)
                    <li class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-950/5 dark:bg-white/5 dark:text-gray-200 dark:ring-white/10">
                        {{ $section }} <span class="font-semibold text-primary-600 tabular-nums dark:text-primary-400">{{ $count }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <p class="ms-auto text-xs text-gray-400 dark:text-gray-500">Se actualiza cada 30 segundos</p>
    </div>
</x-filament-widgets::widget>
