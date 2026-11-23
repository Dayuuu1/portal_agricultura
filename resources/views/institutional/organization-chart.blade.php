<x-layouts.app title="Organigrama" description="Estructura orgánica de la Dirección Regional.">
    <div class="print:hidden">
        <x-page-header title="Organigrama" subtitle="Estructura orgánica de la entidad. Seleccione un órgano para ver sus funciones, su responsable y sus documentos." :breadcrumbs="['Institucional' => null, 'Organigrama' => null]" />
    </div>

    <div class="container-site mt-10 space-y-10 print:mt-0 print:max-w-none print:px-0">
        @forelse ($charts as $chart)
            <section class="orgchart card p-5 sm:p-8 lg:p-10 print:p-0 print:shadow-none print:ring-0" aria-labelledby="organigrama-{{ $loop->index }}">
                <header class="mb-8 space-y-5 lg:mb-10 print:mb-6">
                    <x-site.logo class="hidden print:flex" />
                    <div class="flex flex-wrap items-end justify-between gap-x-8 gap-y-4">
                        <div>
                            <p class="text-sm font-semibold text-brand-700">Estructura orgánica</p>
                            <h2 id="organigrama-{{ $loop->index }}" class="font-display text-2xl font-bold text-ink sm:text-3xl">{{ $chart->head->name }}</h2>
                            @if ($updatedAt)
                                <p class="mt-1 hidden text-sm text-slate-500 print:block">Actualizado el {{ $updatedAt->translatedFormat('j \d\e F \d\e Y') }}</p>
                            @endif
                        </div>
                        <button type="button" x-data @click="window.print()" class="btn-secondary print:hidden">
                            <x-heroicon-o-printer class="size-5" /> Imprimir o guardar en PDF
                        </button>
                    </div>
                    <ul class="flex flex-wrap gap-x-5 gap-y-2 border-t border-slate-100 pt-4 text-sm text-slate-600" aria-label="Tipos de órgano">
                        @foreach ($chart->typesShown() as $type)
                            <li class="flex items-center gap-2">
                                <span class="oc-swatch" data-type="{{ $type->value }}"></span>
                                {{ $type->getLabel() }}
                            </li>
                        @endforeach
                    </ul>
                </header>

                {{-- Diagrama: desde pantallas medianas y al imprimir. --}}
                <div class="hidden lg:block print:block">
                    <div class="flex justify-center">
                        <a href="{{ route('institutional.unit', $chart->head) }}" class="group relative isolate block w-full max-w-md overflow-hidden rounded-2xl bg-brand-900 px-8 pt-5 pb-6 text-center text-white shadow-lift ring-1 ring-brand-950 transition-shadow focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                            <x-site.contours class="absolute inset-0 -z-10 size-full text-brand-300/25" />
                            <span class="block text-sm font-semibold text-gold-300">{{ $chart->head->type->getLabel() }}</span>
                            <span class="mt-0.5 block font-display text-2xl font-extrabold group-hover:underline group-hover:decoration-gold-400 group-hover:underline-offset-4">{{ $chart->head->name }}</span>
                            @if ($chart->head->head_name)
                                <span class="mt-2 inline-flex items-center gap-1.5 text-sm text-brand-100">
                                    <x-heroicon-o-user class="size-4 shrink-0" aria-hidden="true" />
                                    <span><span class="sr-only">Responsable: </span>{{ $chart->head->head_name }}</span>
                                </span>
                            @endif
                            <span class="absolute inset-x-0 bottom-0 h-1 bg-gold-400" aria-hidden="true"></span>
                        </a>
                    </div>

                    @if ($chart->staffRows !== [])
                        <div @class(['oc-staff', 'has-bottom' => $chart->hasBottomRow()])>
                            @foreach ($chart->staffRows as $row)
                                <div class="oc-side-left">
                                    @if ($row['left'])
                                        <x-org-chart.branch :unit="$row['left']" />
                                    @endif
                                </div>
                                <div class="oc-side-right">
                                    @if ($row['right'])
                                        <x-org-chart.branch :unit="$row['right']" />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @elseif ($chart->hasBottomRow())
                        <div class="oc-spine" aria-hidden="true"></div>
                    @endif

                    @if ($chart->hasBottomRow())
                        <ul class="oc-bottom">
                            @foreach ($chart->bottomRow as $unit)
                                <li><x-org-chart.branch :unit="$unit" /></li>
                            @endforeach
                            @foreach ($chart->agencies as $agency)
                                <li><x-org-chart.agency :agency="$agency" /></li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- En pantallas angostas el mismo organigrama se lee de arriba abajo, como un árbol. --}}
                <div class="lg:hidden print:hidden">
                    <a href="{{ route('institutional.unit', $chart->head) }}" class="relative isolate block overflow-hidden rounded-2xl bg-brand-900 px-5 pt-4 pb-5 text-white ring-1 ring-brand-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                        <x-site.contours class="absolute inset-0 -z-10 size-full text-brand-300/25" />
                        <span class="block text-sm font-semibold text-gold-300">{{ $chart->head->type->getLabel() }}</span>
                        <span class="block font-display text-xl font-extrabold">{{ $chart->head->name }}</span>
                        @if ($chart->head->head_name)
                            <span class="mt-1 block text-sm text-brand-100"><span class="sr-only">Responsable: </span>{{ $chart->head->head_name }}</span>
                        @endif
                        <span class="absolute inset-x-0 bottom-0 h-1 bg-gold-400" aria-hidden="true"></span>
                    </a>
                    <ul class="oc-units" aria-label="Órganos de {{ $chart->head->name }}">
                        @foreach ($chart->bodies() as $unit)
                            <li>
                                <x-org-chart.box :unit="$unit" show-type />
                                @if ($unit->children->isNotEmpty())
                                    <ul class="oc-units" aria-label="Unidades de {{ $unit->name }}">
                                        @foreach ($unit->children as $child)
                                            <li><x-org-chart.box :unit="$child" /></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                        @foreach ($chart->agencies as $agency)
                            <li><x-org-chart.agency :agency="$agency" show-type /></li>
                        @endforeach
                    </ul>
                </div>

                @if ($updatedAt)
                    <p class="mt-10 border-t border-slate-100 pt-5 text-sm text-slate-500 print:hidden">
                        Actualizado el <time datetime="{{ $updatedAt->toDateString() }}">{{ $updatedAt->translatedFormat('j \d\e F \d\e Y') }}</time>
                        según las unidades registradas en el portal.
                    </p>
                @endif
            </section>
        @empty
            <x-empty-state icon="heroicon-o-building-office-2" title="Organigrama en preparación" message="La estructura orgánica de la entidad aún no se ha publicado." />
        @endforelse
    </div>
</x-layouts.app>
