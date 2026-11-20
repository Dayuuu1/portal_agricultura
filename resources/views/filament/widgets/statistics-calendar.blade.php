@php
    use Filament\Support\Icons\Heroicon;

    $cellClasses = [
        'published' => 'bg-primary-600 text-white hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400',
        'draft' => 'bg-warning-50 text-warning-700 ring-1 ring-warning-300 ring-inset hover:bg-warning-100 dark:bg-warning-500/10 dark:text-warning-400 dark:ring-warning-500/40',
        'missing' => 'border-2 border-dashed border-gray-300 text-transparent dark:border-white/15',
        'upcoming' => 'bg-gray-50 dark:bg-white/5',
    ];
@endphp

<x-filament-widgets::widget>
    <x-filament::section
        :heading="'Calendario de estadística ' . $year"
        :description="$missing > 0
            ? trans_choice('{1} Hay 1 mes vencido sin documentos.|[2,*] Hay :count meses vencidos sin documentos.', $missing) . ' Haga clic en una casilla punteada para subirlo.'
            : 'Todos los meses vencidos tienen documentos.'"
        :icon="Heroicon::OutlinedCalendarDays"
    >
        <x-slot name="afterHeader">
            <div class="flex items-center gap-1" role="group" aria-label="Cambiar de año">
                <x-filament::icon-button
                    :icon="Heroicon::ChevronLeft"
                    color="gray"
                    wire:click="previousYear"
                    :disabled="! $canGoBack"
                    label="Año anterior"
                />
                <span class="min-w-12 text-center text-sm font-semibold text-gray-950 tabular-nums dark:text-white" aria-live="polite">{{ $year }}</span>
                <x-filament::icon-button
                    :icon="Heroicon::ChevronRight"
                    color="gray"
                    wire:click="nextYear"
                    :disabled="! $canGoForward"
                    label="Año siguiente"
                />
            </div>
        </x-slot>

        <div class="overflow-x-auto" wire:loading.class="opacity-60">
            <table class="w-full min-w-184 border-separate border-spacing-x-0 border-spacing-y-1.5 text-sm">
                <caption class="sr-only">Documentos de estadística por mes en {{ $year }}</caption>
                <thead>
                    <tr>
                        <th scope="col" class="sticky left-0 z-10 bg-white pb-1 text-start text-xs font-medium text-gray-500 dark:bg-gray-900 dark:text-gray-400">Tipo de documento</th>
                        @foreach ($months as $index => $month)
                            <th scope="col" class="w-11 px-0.5 pb-1 text-center text-xs font-medium text-gray-500 dark:text-gray-400">
                                <abbr title="{{ \Illuminate\Support\Str::ucfirst(\Illuminate\Support\Carbon::create(2000, $index + 1)->translatedFormat('F')) }}" class="no-underline">{{ $month }}</abbr>
                            </th>
                        @endforeach
                        <th scope="col" class="w-24 pb-1"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <th scope="row" class="sticky left-0 z-10 min-w-40 bg-white py-1 pe-3 text-start align-middle font-normal dark:bg-gray-900">
                                <span class="block font-semibold text-gray-950 dark:text-white">{{ $row['name'] }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">
                                    {{ trans_choice('{0} Sin documentos|{1} 1 documento|[2,*] :count documentos', $row['total']) }}
                                    @if ($row['withoutMonth'] > 0)
                                        · {{ trans_choice('{1} 1 sin mes en el título|[2,*] :count sin mes en el título', $row['withoutMonth']) }}
                                    @endif
                                </span>
                            </th>
                            @foreach ($row['cells'] as $cell)
                                <td class="px-0.5 py-0 align-middle">
                                    @if ($cell['url'])
                                        <a href="{{ $cell['url'] }}" title="{{ $cell['label'] }}" aria-label="{{ $cell['label'] }}"
                                            @class([
                                                'group flex h-9 w-full items-center justify-center rounded-md text-xs font-semibold tabular-nums transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600',
                                                $cellClasses[$cell['state']],
                                                'hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-500/10' => $cell['state'] === 'missing',
                                            ])>
                                            @if ($cell['state'] === 'missing')
                                                <x-filament::icon :icon="Heroicon::Plus" class="h-4 w-4 text-gray-400 opacity-0 transition group-hover:text-primary-600 group-hover:opacity-100 group-focus-visible:opacity-100" />
                                            @else
                                                {{ $cell['count'] }}
                                            @endif
                                        </a>
                                    @else
                                        <span title="{{ $cell['label'] }}" @class(['flex h-9 w-full items-center justify-center rounded-md', $cellClasses[$cell['state']]])>
                                            <span class="sr-only">{{ $cell['label'] }}</span>
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="ps-2 text-end align-middle">
                                @if ($row['uploadUrl'])
                                    <x-filament::link :href="$row['uploadUrl']" :icon="Heroicon::OutlinedArrowUpTray" size="sm">
                                        Subir<span class="sr-only"> {{ $row['name'] }}</span>
                                    </x-filament::link>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <ul class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-gray-600 dark:text-gray-400" aria-label="Leyenda">
            <li class="flex items-center gap-1.5"><span class="h-3.5 w-3.5 rounded {{ $cellClasses['published'] }}"></span> Publicado (número de documentos)</li>
            <li class="flex items-center gap-1.5"><span class="h-3.5 w-3.5 rounded {{ $cellClasses['draft'] }}"></span> Subido, sin publicar</li>
            <li class="flex items-center gap-1.5"><span class="h-3.5 w-3.5 rounded border-2 border-dashed border-gray-300 dark:border-white/15"></span> Falta subir</li>
            <li class="flex items-center gap-1.5"><span class="h-3.5 w-3.5 rounded {{ $cellClasses['upcoming'] }} ring-1 ring-gray-200 ring-inset dark:ring-white/10"></span> Aún no corresponde</li>
            <li class="basis-full text-gray-500 dark:text-gray-400">El mes se toma del título del documento, igual que en el portal: «Producción agrícola julio {{ $year }}» va a julio.</li>
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
