{{-- Live preview of the browser tab and the portal header, built from the values being edited. --}}
<div class="overflow-hidden rounded-xl ring-1 ring-gray-950/10 dark:ring-white/10" aria-label="Vista previa del encabezado del portal">
    <div class="flex items-end gap-2 bg-gray-100 px-3 pt-2.5 dark:bg-white/5">
        <span class="flex gap-1.5 pb-3 ps-1" aria-hidden="true">
            <span class="h-2.5 w-2.5 rounded-full bg-gray-300 dark:bg-white/20"></span>
            <span class="h-2.5 w-2.5 rounded-full bg-gray-300 dark:bg-white/20"></span>
            <span class="h-2.5 w-2.5 rounded-full bg-gray-300 dark:bg-white/20"></span>
        </span>
        <span class="flex max-w-xs min-w-0 items-center gap-2 rounded-t-lg bg-white px-3 py-2 text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-200">
            <img src="{{ $faviconUrl }}" alt="" class="h-4 w-4 shrink-0 object-contain">
            <span class="truncate">Inicio | {{ filled($siteName) ? $siteName : 'Nombre oficial de la entidad' }}</span>
        </span>
    </div>

    <div class="flex items-center gap-3 border-b border-gray-200 bg-white px-5 py-4 dark:border-white/10 dark:bg-gray-900">
        <img src="{{ $logoUrl }}" alt="" class="h-11 w-auto max-w-28 shrink-0 object-contain">
        <span class="min-w-0 leading-tight">
            @if (filled($parentEntity))
                <span class="block truncate text-[0.68rem] font-semibold tracking-wider text-primary-600 uppercase dark:text-primary-400">{{ $parentEntity }}</span>
            @endif
            <span @class(['block font-bold', 'text-primary-900 dark:text-white' => filled($shortName), 'text-gray-400' => blank($shortName)])>
                {{ filled($shortName) ? $shortName : 'Nombre corto' }}
            </span>
        </span>
    </div>

    <div class="flex gap-5 bg-primary-800 px-5 py-2.5 text-xs font-medium text-white/85" aria-hidden="true">
        <span class="text-white">Inicio</span>
        <span>Institucional</span>
        <span class="hidden sm:inline">Información agraria</span>
        <span class="hidden sm:inline">Noticias</span>
        <span class="hidden md:inline">Documentos</span>
    </div>
</div>
