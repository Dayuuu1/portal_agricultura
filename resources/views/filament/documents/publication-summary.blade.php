{{-- Where the document will show on the public site; refreshed as the form changes. --}}
@php
    use Filament\Support\Icons\Heroicon;
@endphp

<div class="rounded-xl bg-gray-50 p-4 text-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Dónde aparecerá en el portal</p>

    @if ($type)
        <ol class="mt-2 flex flex-wrap items-center gap-x-1.5 gap-y-1 font-medium text-gray-950 dark:text-white" aria-label="Ubicación en el portal">
            <li>Documentos</li>
            @foreach (array_filter([$section, $type, $year]) as $step)
                <li aria-hidden="true" class="text-gray-400">›</li>
                <li>{{ $step }}</li>
            @endforeach
        </ol>

        @if ($expectsMonth)
            <p @class([
                'mt-2 flex gap-1.5',
                'text-success-700 dark:text-success-400' => $period,
                'text-warning-700 dark:text-warning-400' => ! $period,
            ])>
                <x-filament::icon :icon="$period ? Heroicon::OutlinedCalendarDays : Heroicon::OutlinedExclamationTriangle" class="mt-0.5 h-4 w-4 shrink-0" />
                {{ $period ? "Entre las publicaciones de {$period}." : 'Sin mes en el título: se mostrará por su fecha de publicación.' }}
            </p>
        @endif
    @else
        <p class="mt-2 text-gray-600 dark:text-gray-300">Elija el tipo de documento para ver en qué sección se publicará.</p>
    @endif

    <p @class([
        'mt-3 flex gap-1.5 border-t border-gray-200 pt-3 dark:border-white/10',
        'text-gray-700 dark:text-gray-200' => $isPublished,
        'text-gray-500 dark:text-gray-400' => ! $isPublished,
    ])>
        <x-filament::icon :icon="$isPublished ? Heroicon::OutlinedEye : Heroicon::OutlinedEyeSlash" class="mt-0.5 h-4 w-4 shrink-0" />
        {{ $isPublished ? 'Visible para el público al guardar.' : 'Se guardará oculto, sin publicar.' }}
    </p>

    @if ($url)
        <p class="mt-1 truncate ps-5.5 text-xs text-gray-500 dark:text-gray-400" title="{{ $url }}">{{ $url }}</p>
    @endif
</div>
