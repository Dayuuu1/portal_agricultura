{{-- Paginación del portal público: en español y con los colores institucionales. --}}
@if ($paginator->hasPages())
    @php
        $pageClasses = 'inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl px-3 text-sm font-semibold transition duration-200';
        $idleClasses = 'bg-white text-slate-700 ring-1 ring-slate-200 ring-inset hover:-translate-y-px hover:text-brand-800 hover:ring-brand-300 active:scale-95';
        $disabledClasses = 'cursor-not-allowed bg-white/60 text-slate-300 ring-1 ring-slate-200 ring-inset';
    @endphp

    <nav role="navigation" aria-label="Paginación" class="flex flex-col items-center gap-4 sm:flex-row sm:justify-between">
        <p class="order-2 text-sm text-slate-500 sm:order-1">
            @if ($paginator->firstItem())
                Mostrando <span class="font-semibold text-slate-700 tabular-nums">{{ $paginator->firstItem() }}</span>
                a <span class="font-semibold text-slate-700 tabular-nums">{{ $paginator->lastItem() }}</span>
                de <span class="font-semibold text-slate-700 tabular-nums">{{ number_format($paginator->total()) }}</span> resultados
            @else
                {{ $paginator->count() }} resultados
            @endif
        </p>

        <ul class="order-1 flex flex-wrap items-center justify-center gap-1.5 sm:order-2">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="{{ $pageClasses }} {{ $disabledClasses }}" aria-disabled="true">
                        <x-heroicon-m-chevron-left class="size-5" /><span class="sr-only">Página anterior</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $pageClasses }} {{ $idleClasses }}">
                        <x-heroicon-m-chevron-left class="size-5" /><span class="sr-only">Página anterior</span>
                    </a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="hidden sm:block"><span class="{{ $pageClasses }} text-slate-400" aria-hidden="true">…</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @php($isNearCurrent = abs($page - $paginator->currentPage()) <= 1 || $page === 1 || $page === $paginator->lastPage())
                        <li @class(['hidden sm:block' => ! $isNearCurrent])>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="{{ $pageClasses }} bg-brand-600 text-white shadow-sm shadow-brand-900/20 tabular-nums">
                                    <span class="sr-only">Página </span>{{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="{{ $pageClasses }} {{ $idleClasses }} tabular-nums" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $pageClasses }} {{ $idleClasses }}">
                        <x-heroicon-m-chevron-right class="size-5" /><span class="sr-only">Página siguiente</span>
                    </a>
                @else
                    <span class="{{ $pageClasses }} {{ $disabledClasses }}" aria-disabled="true">
                        <x-heroicon-m-chevron-right class="size-5" /><span class="sr-only">Página siguiente</span>
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
