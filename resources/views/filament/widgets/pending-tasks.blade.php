<x-filament-widgets::widget>
    <x-filament::section
        heading="Pendientes"
        description="Aspectos de sus módulos que conviene revisar."
        :icon="\Filament\Support\Icons\Heroicon::OutlinedClipboardDocumentCheck"
    >
        @if ($tasks === [])
            <div class="flex flex-col items-center gap-2 py-6 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-500/10">
                    <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedCheckBadge" class="h-6 w-6" />
                </span>
                <p class="text-sm font-semibold text-gray-950 dark:text-white">¡Todo al día!</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">No hay tareas pendientes en sus áreas.</p>
            </div>
        @else
            <ul class="-my-2 divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($tasks as $task)
                    <li>
                        <a href="{{ $task['url'] }}" class="group flex items-center gap-3 py-3">
                            <x-filament::icon :icon="$task['icon']" class="h-5 w-5 shrink-0 text-gray-400 group-hover:text-primary-600" />
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-gray-950 group-hover:text-primary-700 dark:text-white">{{ $task['label'] }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $task['hint'] }}</span>
                            </span>
                            <x-filament::badge :color="$task['color']">{{ $task['badge'] ?? number_format($task['count']) }}</x-filament::badge>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
