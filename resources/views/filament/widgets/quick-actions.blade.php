<x-filament-widgets::widget>
    <x-filament::section
        heading="¿Qué desea hacer hoy?"
        description="Accesos directos a las tareas más frecuentes de sus áreas."
        :icon="\Filament\Support\Icons\Heroicon::OutlinedBolt"
    >
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($actions as $action)
                <a href="{{ $action['url'] }}"
                    class="group flex items-start gap-3 rounded-xl border border-gray-200 p-4 transition hover:border-primary-400 hover:bg-primary-50/60 dark:border-white/10 dark:hover:bg-white/5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 transition group-hover:bg-primary-600 group-hover:text-white dark:bg-primary-500/10 dark:text-primary-400">
                        <x-filament::icon :icon="$action['icon']" class="h-5 w-5" />
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-gray-950 dark:text-white">{{ $action['label'] }}</span>
                        <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">{{ $action['description'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
