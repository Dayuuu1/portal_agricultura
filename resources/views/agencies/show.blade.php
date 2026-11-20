<x-layouts.app :title="$agency->name" :description="strip_tags((string) $agency->description)">
    <x-page-header :title="$agency->name" :subtitle="'Provincia de '.$agency->province" :breadcrumbs="['Agencias agrarias' => route('agencies.index'), $agency->name => null]" />

    <div class="container-site mt-10 grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if ($agency->photo_url)
                <img src="{{ $agency->photo_url }}" alt="{{ $agency->name }}" class="aspect-video w-full rounded-2xl object-cover">
            @endif
            @if ($agency->description)
                <div class="card p-6 sm:p-8">
                    <h2 class="mb-3 text-lg font-bold text-slate-900">Presentación</h2>
                    <div class="prose prose-slate max-w-none">{!! str($agency->description)->sanitizeHtml() !!}</div>
                </div>
            @endif
            @if ($agency->services)
                <div class="card p-6 sm:p-8">
                    <h2 class="mb-4 text-lg font-bold text-slate-900">Servicios</h2>
                    <ul class="grid gap-3 sm:grid-cols-2">
                        @foreach ($agency->services as $service)
                            <li class="flex items-start gap-2.5 rounded-xl bg-brand-50 p-3 text-sm font-medium text-brand-900">
                                <x-heroicon-o-check-circle class="size-5 shrink-0 text-brand-600" /> {{ $service }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <aside class="space-y-6">
            <div class="card p-6">
                <h2 class="mb-4 font-bold text-slate-900">Datos de contacto</h2>
                <dl class="space-y-3 text-sm">
                    @if ($agency->head_name)<div><dt class="text-slate-500">Responsable</dt><dd class="font-semibold">{{ $agency->head_name }}</dd></div>@endif
                    @if ($agency->address)<div><dt class="text-slate-500">Dirección</dt><dd class="font-semibold">{{ $agency->address }}{{ $agency->district ? ', '.$agency->district : '' }}</dd></div>@endif
                    @if ($agency->phone)<div><dt class="text-slate-500">Teléfono</dt><dd class="font-semibold">{{ $agency->phone }}</dd></div>@endif
                    @if ($agency->email)<div><dt class="text-slate-500">Correo</dt><dd><a href="mailto:{{ $agency->email }}" class="font-semibold text-brand-700">{{ $agency->email }}</a></dd></div>@endif
                    @if ($agency->schedule)<div><dt class="text-slate-500">Horario</dt><dd class="font-semibold">{{ $agency->schedule }}</dd></div>@endif
                </dl>
                @if ($agency->map_url)
                    <a href="{{ $agency->map_url }}" target="_blank" rel="noopener" class="btn-primary mt-6 w-full"><x-heroicon-o-map-pin class="size-5" /> Ver en el mapa</a>
                @endif
            </div>
            @if ($otherAgencies->isNotEmpty())
                <div class="card p-6">
                    <h2 class="mb-3 font-bold text-slate-900">Otras agencias</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach ($otherAgencies as $other)
                            <li><a href="{{ route('agencies.show', $other) }}" class="font-medium text-brand-700 hover:underline">{{ $other->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</x-layouts.app>
