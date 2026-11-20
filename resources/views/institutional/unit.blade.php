<x-layouts.app :title="$unit->name" :description="$unit->description">
    <x-page-header :title="$unit->name" :subtitle="$unit->type->getLabel()" :breadcrumbs="array_filter([
        'Organigrama' => route('institutional.organization-chart'),
        $unit->parent?->name ?? '' => $unit->parent ? route('institutional.unit', $unit->parent) : null,
        $unit->name => null,
    ], fn ($url, $label) => $label !== '', ARRAY_FILTER_USE_BOTH)" />

    <div class="container-site mt-10 grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if ($unit->description)
                <div class="card p-6 sm:p-8">
                    <h2 class="mb-3 text-lg font-bold text-slate-900">Presentación</h2>
                    <p class="leading-relaxed text-slate-700">{{ $unit->description }}</p>
                </div>
            @endif

            @if ($unit->functions)
                <div class="card p-6 sm:p-8">
                    <h2 class="mb-3 text-lg font-bold text-slate-900">Funciones</h2>
                    <div class="prose prose-slate max-w-none">{!! str($unit->functions)->sanitizeHtml() !!}</div>
                </div>
            @endif

            @if ($unit->children->isNotEmpty())
                <div class="card p-6 sm:p-8">
                    <h2 class="mb-4 text-lg font-bold text-slate-900">Unidades que la conforman</h2>
                    <ul class="grid gap-3 sm:grid-cols-2">
                        @foreach ($unit->children as $child)
                            <li>
                                <a href="{{ route('institutional.unit', $child) }}" class="flex items-center justify-between gap-2 rounded-xl bg-slate-50 p-4 font-semibold text-slate-800 hover:bg-brand-50 hover:text-brand-800">
                                    {{ $child->name }} <x-heroicon-o-arrow-right class="size-4 shrink-0" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($documents->isNotEmpty())
                <div>
                    <h2 class="mb-4 text-lg font-bold text-slate-900">Documentos publicados</h2>
                    <ul class="card divide-y divide-slate-100 overflow-hidden">
                        @foreach ($documents as $document)
                            <x-document-item :document="$document" />
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <aside>
            <div class="card p-6">
                <div class="mb-4 flex size-16 items-center justify-center overflow-hidden rounded-full bg-brand-100">
                    @if ($unit->head_photo_url)
                        <img src="{{ $unit->head_photo_url }}" alt="{{ $unit->head_name }}" class="size-full object-cover">
                    @else
                        <x-heroicon-o-user class="size-8 text-brand-600" />
                    @endif
                </div>
                <p class="text-sm font-semibold text-brand-600">{{ $unit->head_title ?? 'Responsable' }}</p>
                <p class="font-display text-lg font-bold text-slate-900">{{ $unit->head_name ?: 'Por actualizar' }}</p>
                <dl class="mt-4 space-y-2 text-sm">
                    @if ($unit->email)
                        <div class="flex items-center gap-2"><dt><x-heroicon-o-envelope class="size-4 text-brand-600" /><span class="sr-only">Correo</span></dt><dd><a href="mailto:{{ $unit->email }}" class="hover:text-brand-700">{{ $unit->email }}</a></dd></div>
                    @endif
                    @if ($unit->phone)
                        <div class="flex items-center gap-2"><dt><x-heroicon-o-phone class="size-4 text-brand-600" /><span class="sr-only">Teléfono</span></dt><dd>{{ $unit->phone }}</dd></div>
                    @endif
                </dl>
            </div>
        </aside>
    </div>
</x-layouts.app>
