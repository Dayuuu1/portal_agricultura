<x-layouts.app title="Agencias agrarias" description="Agencias agrarias de Madre de Dios: ubicación, horarios y servicios.">
    <x-page-header title="Agencias agrarias" subtitle="Órganos desconcentrados que brindan atención directa a los productores en las provincias de Madre de Dios." :breadcrumbs="['Institucional' => null, 'Agencias agrarias' => null]" />

    <div class="container-site mt-10">
        @if ($agencies->isNotEmpty())
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($agencies as $agency)
                    <article class="card group flex flex-col overflow-hidden transition hover:shadow-md">
                        @if ($agency->photo_url)
                            <img src="{{ $agency->photo_url }}" alt="{{ $agency->name }}" class="aspect-video w-full object-cover" loading="lazy">
                        @else
                            <div class="relative flex aspect-video items-center justify-center bg-linear-to-br from-brand-600 to-brand-800">
                                <div class="bg-pattern absolute inset-0"></div>
                                <x-heroicon-o-map-pin class="relative size-12 text-white/50" />
                            </div>
                        @endif
                        <div class="flex flex-1 flex-col p-6">
                            <p class="text-xs font-semibold tracking-wider text-brand-600 uppercase">Provincia de {{ $agency->province }}</p>
                            <h2 class="mt-1 text-lg font-bold text-slate-900 group-hover:text-brand-700">
                                <a href="{{ route('agencies.show', $agency) }}">{{ $agency->name }}</a>
                            </h2>
                            <ul class="mt-3 space-y-1.5 text-sm text-slate-600">
                                @if ($agency->address)<li class="flex gap-2"><x-heroicon-o-map-pin class="mt-0.5 size-4 shrink-0 text-brand-600" /> {{ $agency->address }}</li>@endif
                                @if ($agency->phone)<li class="flex gap-2"><x-heroicon-o-phone class="mt-0.5 size-4 shrink-0 text-brand-600" /> {{ $agency->phone }}</li>@endif
                                @if ($agency->schedule)<li class="flex gap-2"><x-heroicon-o-clock class="mt-0.5 size-4 shrink-0 text-brand-600" /> {{ $agency->schedule }}</li>@endif
                            </ul>
                            <a href="{{ route('agencies.show', $agency) }}" class="mt-auto inline-flex items-center gap-1.5 pt-5 text-sm font-semibold text-brand-700 hover:text-brand-900">
                                Ver servicios <x-heroicon-o-arrow-right class="size-4" />
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <x-empty-state icon="heroicon-o-map-pin" title="No hay agencias registradas" />
        @endif
    </div>
</x-layouts.app>
