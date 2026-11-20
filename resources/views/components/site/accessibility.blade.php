{{-- Barra de accesibilidad: botón fijo en el borde derecho (Ley N° 29973, WCAG 2.1). Lógica en resources/js/accessibility.js. --}}
@php
    $options = [
        'contrast' => ['Alto contraste', 'contrast'],
        'grayscale' => ['Escala de grises', 'swatch'],
        'links' => ['Resaltar enlaces', 'link'],
        'dyslexia' => ['Fuente para dislexia', 'book-open'],
        'noMotion' => ['Detener animaciones', 'pause-circle'],
    ];
@endphp

<div x-data="accessibility" @keydown.window="onKeydown($event)" @click.outside="close(false)" class="a11y-widget print:hidden">
    <button type="button" x-ref="trigger" @click="toggle()" :aria-expanded="open.toString()" aria-controls="panel-accesibilidad"
        title="Accesibilidad (Alt + 0)"
        class="fixed top-1/2 right-0 z-40 flex size-12 -translate-y-1/2 items-center justify-center rounded-l-2xl bg-brand-700 text-white shadow-lg ring-2 ring-white transition hover:bg-brand-800 hover:pr-1 focus-visible:outline-offset-0">
        <span class="sr-only">Opciones de accesibilidad</span>
        <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10" />
            <circle cx="12" cy="6.9" r="1.5" fill="currentColor" stroke="none" />
            <path d="M6.8 9.6 12 10.7l5.2-1.1" />
            <path d="M12 10.7v3.4l-2.6 4.4M12 14.1l2.6 4.4" />
        </svg>
    </button>

    <div x-cloak x-show="open" x-ref="panel" x-transition.opacity.duration.150ms
        id="panel-accesibilidad" role="dialog" aria-labelledby="titulo-accesibilidad" tabindex="-1"
        @keydown.escape.stop="close()"
        class="fixed top-1/2 right-3 z-50 max-h-[calc(100dvh-1.5rem)] w-80 max-w-[calc(100vw-1.5rem)] -translate-y-1/2 overflow-y-auto rounded-2xl bg-white text-slate-800 shadow-2xl ring-1 ring-slate-200 focus:outline-none">
        <div class="flex items-center justify-between gap-3 rounded-t-2xl bg-brand-800 px-4 py-3 text-white">
            <h2 id="titulo-accesibilidad" class="flex items-center gap-2 text-base font-bold">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" />
                    <circle cx="12" cy="6.9" r="1.5" fill="currentColor" stroke="none" />
                    <path d="M6.8 9.6 12 10.7l5.2-1.1" />
                    <path d="M12 10.7v3.4l-2.6 4.4M12 14.1l2.6 4.4" />
                </svg>
                Accesibilidad
            </h2>
            <button type="button" @click="close()" class="rounded-lg p-1 text-white/80 hover:bg-white/10 hover:text-white">
                <span class="sr-only">Cerrar opciones de accesibilidad</span>
                <x-heroicon-o-x-mark class="size-5" />
            </button>
        </div>

        <div class="space-y-5 p-4">
            <section aria-labelledby="a11y-tamano">
                <h3 id="a11y-tamano" class="mb-2 text-sm font-semibold text-slate-700">Tamaño del texto</h3>
                <div class="flex items-stretch gap-2">
                    <button type="button" @click="changeFontScale(-1)" :disabled="settings.fontScale <= 80"
                        class="flex-1 rounded-lg bg-slate-100 py-2 text-base font-bold text-slate-800 hover:bg-brand-50 hover:text-brand-800 disabled:opacity-40">
                        A<span aria-hidden="true">−</span><span class="sr-only">: reducir texto</span>
                    </button>
                    <button type="button" @click="changeFontScale(0)" class="flex-1 rounded-lg py-2 text-sm font-semibold text-slate-600 ring-1 ring-slate-200 ring-inset hover:bg-slate-50"
                        :aria-label="`Tamaño actual ${settings.fontScale} %. Pulse para volver al 100 %`">
                        <span x-text="`${settings.fontScale}%`">100%</span>
                    </button>
                    <button type="button" @click="changeFontScale(1)" :disabled="settings.fontScale >= 200"
                        class="flex-1 rounded-lg bg-slate-100 py-2 text-lg font-bold text-slate-800 hover:bg-brand-50 hover:text-brand-800 disabled:opacity-40">
                        A<span aria-hidden="true">+</span><span class="sr-only">: aumentar texto</span>
                    </button>
                </div>
            </section>

            <section aria-label="Ajustes de visualización" class="grid grid-cols-2 gap-2">
                @foreach ($options as $key => [$label, $icon])
                    <button type="button" @click="toggleOption('{{ $key }}')" :aria-pressed="settings.{{ $key }}.toString()"
                        @class(['flex flex-col items-center gap-1.5 rounded-xl px-2 py-3 text-center text-sm font-semibold ring-1 ring-inset transition', 'col-span-2' => $loop->last && $loop->count % 2 === 1])
                        :class="settings.{{ $key }} ? 'bg-brand-700 text-white ring-brand-700' : 'bg-white text-slate-700 ring-slate-200 hover:bg-brand-50 hover:ring-brand-200'">
                        @if ($icon === 'contrast')
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 3a9 9 0 0 1 0 18Z" fill="currentColor" />
                            </svg>
                        @else
                            <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-6" />
                        @endif
                        {{ $label }}
                    </button>
                @endforeach
            </section>

            <section aria-labelledby="a11y-lectura">
                <h3 id="a11y-lectura" class="mb-2 text-sm font-semibold text-slate-700">Lectura</h3>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" @pointerdown="rememberSelection()" @click="speak()"
                        class="flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-3 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">
                        <x-heroicon-o-speaker-wave class="size-5" /> Leer en voz alta
                    </button>
                    <button type="button" @click="togglePause()" :disabled="! speaking"
                        class="flex items-center justify-center gap-2 rounded-lg bg-slate-100 px-3 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-200 disabled:opacity-40">
                        <x-heroicon-o-pause x-show="! paused" class="size-5" />
                        <x-heroicon-o-play x-show="paused" x-cloak class="size-5" />
                        <span x-text="paused ? 'Continuar' : 'Pausar'">Pausar</span>
                    </button>
                </div>
                <p class="mt-2 text-xs text-slate-500">Lee el texto que haya seleccionado o, si no hay selección, el contenido principal de la página.</p>
                <p x-show="! canSpeak" x-cloak class="mt-1 text-xs font-semibold text-red-700">Su navegador no permite la lectura en voz alta.</p>
            </section>

            <button type="button" @click="reset()"
                class="flex w-full items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-sm font-semibold text-brand-800 ring-1 ring-brand-200 ring-inset hover:bg-brand-50">
                <x-heroicon-o-arrow-path class="size-5" /> Restablecer configuración
            </button>

            <p class="border-t border-slate-100 pt-3 text-xs leading-relaxed text-slate-500">
                Herramientas conforme a la Ley N° 29973 y WCAG 2.1 nivel AA. Atajo: <kbd class="rounded bg-slate-100 px-1 font-sans font-semibold text-slate-700">Alt</kbd> + <kbd class="rounded bg-slate-100 px-1 font-sans font-semibold text-slate-700">0</kbd>.
            </p>
        </div>
    </div>

    <p class="sr-only" aria-live="polite" x-text="announcement"></p>
</div>
