<x-layouts.app title="Organigrama" description="Estructura orgánica de la Dirección Regional.">
    <x-page-header title="Organigrama" subtitle="Estructura orgánica de la entidad. Seleccione un órgano para ver sus funciones y documentos." :breadcrumbs="['Institucional' => null, 'Organigrama' => null]" />

    <div class="container-site mt-10">
        @foreach ($roots as $root)
            <div class="flex flex-col items-center">
                <a href="{{ route('institutional.unit', $root) }}" class="rounded-2xl bg-brand-800 px-8 py-5 text-center text-white shadow-lg ring-4 ring-brand-100 hover:bg-brand-900">
                    <span class="block text-xs font-semibold tracking-wider text-gold-300 uppercase">{{ $root->type->getLabel() }}</span>
                    <span class="font-display text-xl font-extrabold">{{ $root->name }}</span>
                </a>
                <span class="h-8 w-0.5 bg-brand-200" aria-hidden="true"></span>
            </div>

            @php
                $groups = $root->children->groupBy(fn ($unit) => $unit->type->value);
                $order = ['control', 'asesoramiento', 'apoyo', 'linea', 'desconcentrado', 'unidad'];
            @endphp

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($order as $type)
                    @continue(! $groups->has($type))
                    <section class="rounded-2xl bg-white/60 p-4 ring-1 ring-slate-200">
                        <h2 class="mb-3 text-center text-xs font-bold tracking-wider text-brand-700 uppercase">{{ \App\Enums\UnitType::from($type)->getLabel() }}</h2>
                        <ul class="space-y-3">
                            @foreach ($groups[$type] as $office)
                                <li class="card p-4">
                                    <a href="{{ route('institutional.unit', $office) }}" class="font-bold text-slate-900 hover:text-brand-700">{{ $office->name }}</a>
                                    @if ($office->children->isNotEmpty())
                                        <ul class="mt-3 space-y-1.5 border-l-2 border-brand-100 pl-3">
                                            @foreach ($office->children as $child)
                                                <li><a href="{{ route('institutional.unit', $child) }}" class="text-sm text-slate-600 hover:text-brand-700">{{ $child->name }}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endforeach

        <section class="mt-10 card flex flex-col items-start gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-slate-900">Órganos desconcentrados</h2>
                <p class="text-sm text-slate-600">Las agencias agrarias brindan atención directa a los productores en provincias.</p>
            </div>
            <a href="{{ route('agencies.index') }}" class="btn-primary">Ver agencias agrarias</a>
        </section>
    </div>
</x-layouts.app>
