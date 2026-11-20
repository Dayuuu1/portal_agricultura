<footer class="flex w-full flex-wrap items-center justify-between gap-2 px-4 py-6 text-xs text-gray-500 md:px-6 lg:px-8 dark:text-gray-400">
    <span>&copy; {{ now()->year }} {{ collect([\App\Models\Setting::valueOf('site_name', config('app.name')), \App\Models\Setting::valueOf('parent_entity')])->filter()->implode(' · ') }}</span>
    <span>Todos los cambios quedan registrados en el registro de actividad.</span>
</footer>
