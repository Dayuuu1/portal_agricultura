<x-layouts.app title="Directorio" description="Directorio de autoridades y responsables de los órganos de la entidad.">
    <x-page-header title="Directorio institucional" subtitle="Autoridades y responsables de los órganos de la entidad." :breadcrumbs="['Institucional' => null, 'Directorio' => null]" />

    <div class="container-site mt-10">
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <caption class="sr-only">Directorio de autoridades y responsables</caption>
                    <thead class="bg-slate-50 text-left text-xs font-bold tracking-wider text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-3">Órgano</th>
                            <th scope="col" class="px-5 py-3">Responsable</th>
                            <th scope="col" class="px-5 py-3">Contacto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($units as $unit)
                            <tr class="hover:bg-brand-50/40">
                                <td class="px-5 py-4">
                                    <a href="{{ route('institutional.unit', $unit) }}" class="font-semibold text-slate-900 hover:text-brand-700">{{ $unit->name }}</a>
                                    <span class="block text-xs text-slate-500">{{ $unit->type->getLabel() }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="block font-medium text-slate-800">{{ $unit->head_name ?: 'Por actualizar' }}</span>
                                    <span class="block text-xs text-slate-500">{{ $unit->head_title }}</span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    @if ($unit->email)<a href="mailto:{{ $unit->email }}" class="block hover:text-brand-700">{{ $unit->email }}</a>@endif
                                    @if ($unit->phone)<span class="block">{{ $unit->phone }}</span>@endif
                                    @if (! $unit->email && ! $unit->phone)<span class="text-slate-400">—</span>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
