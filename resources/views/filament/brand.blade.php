<div class="flex items-center gap-3">
    <img src="{{ \App\Models\Setting::logoUrl() }}" alt="" class="h-10 w-auto max-w-24 shrink-0 object-contain">
    <div class="leading-tight">
        {{-- In the mobile drawer the logo sits on the sidebar's green. --}}
        <p class="text-sm font-bold text-gray-950 in-[.fi-sidebar-header]:text-white dark:text-white">{{ \App\Models\Setting::shortName() }}</p>
        <p class="text-xs font-medium text-primary-600 in-[.fi-sidebar-header]:text-[#f1c95f] dark:text-primary-400">Panel de administración</p>
    </div>
</div>
