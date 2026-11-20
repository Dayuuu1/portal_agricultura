<x-filament-widgets::widget>
    <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-primary-700 to-primary-500 p-6 text-white shadow-sm">
        <img src="{{ \App\Models\Setting::logoUrl() }}" alt="" class="pointer-events-none absolute -right-6 -bottom-8 hidden h-40 w-auto opacity-25 sm:block">

        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-medium text-white/80">{{ $greeting }},</p>
                <h2 class="text-2xl font-bold tracking-tight">{{ $user->name }}</h2>
                @if ($user->position)
                    <p class="text-sm text-white/80">{{ $user->position }}</p>
                @endif
                <div class="flex flex-wrap gap-2 pt-1">
                    @foreach ($areas as $area)
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold ring-1 ring-white/25">{{ $area }}</span>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-2 text-sm sm:items-end">
                <p class="text-white/80">
                    Último acceso:
                    <span class="font-semibold text-white">{{ $user->last_login_at?->translatedFormat('j \d\e F, H:i') ?? 'primer ingreso' }}</span>
                </p>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-primary-700 shadow-sm hover:bg-primary-50">
                        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedGlobeAlt" class="h-4 w-4" />
                        Ver portal
                    </a>
                    <a href="{{ filament()->getProfileUrl() }}"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-2 text-sm font-semibold text-white ring-1 ring-white/30 hover:bg-white/20">
                        <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedUserCircle" class="h-4 w-4" />
                        Mi perfil y contraseña
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
