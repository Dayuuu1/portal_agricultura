<div class="flex items-center gap-3">
    <img src="{{ \App\Models\Setting::logoUrl() }}" alt="" class="h-10 w-auto max-w-24 shrink-0 object-contain">
    <div class="leading-tight">
        <p class="text-sm font-bold text-gray-950 dark:text-white">{{ \App\Models\Setting::shortName() }}</p>
        <p class="text-xs font-medium text-primary-600 dark:text-primary-400">Panel de administración</p>
    </div>
</div>
