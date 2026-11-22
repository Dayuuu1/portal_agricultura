{{-- Bottom of the panel's sidebar: a way back to the public portal and the portal's contour lines as texture. --}}
<div class="dra-sidebar-contours" aria-hidden="true">
    <x-site.contours />
</div>

<a
    href="{{ route('home') }}"
    target="_blank"
    rel="noopener"
    class="dra-sidebar-portal-link"
    x-data="filamentSidebarTooltip"
    x-tooltip="getTooltip({ content: 'Ver portal público' })"
>
    <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedGlobeAlt" />
    <span class="min-w-0 flex-1 truncate" x-show="$store.sidebar.isOpen">Ver portal público</span>
    <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedArrowTopRightOnSquare" class="size-4!" x-show="$store.sidebar.isOpen" />
    <span class="sr-only">(se abre en una pestaña nueva)</span>
</a>
