<x-layouts.app title="Directorio" description="Directorio de autoridades y responsables de los órganos de la entidad.">
    <x-page-header title="Directorio institucional" subtitle="Autoridades y responsables de los órganos de la entidad." :breadcrumbs="['Institucional' => null, 'Directorio' => null]" />

    @php
        $telephone = fn (string $phone): string => preg_replace('/[^\d+]/', '', preg_split('/\b(?:anexo|anx|ext)\b/iu', $phone)[0]);
    @endphp

    <div class="container-site mt-10">
        <div class="card overflow-hidden">
            {{-- En pantallas pequeñas cada fila se apila como una ficha; desde «md» es una tabla de tres columnas. --}}
            <table class="w-full text-sm max-md:block">
                <caption class="sr-only">Directorio de autoridades y responsables</caption>
                <thead class="bg-slate-50 text-left text-sm font-semibold text-slate-600 max-md:sr-only">
                    <tr>
                        <th scope="col" class="px-6 py-3.5">Órgano</th>
                        <th scope="col" class="px-6 py-3.5">Responsable</th>
                        <th scope="col" class="px-6 py-3.5">Contacto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 max-md:block">
                    @foreach ($units as $unit)
                        <tr class="align-middle transition-colors hover:bg-brand-50/40 max-md:grid max-md:gap-3 max-md:p-5">
                            <td class="px-6 py-4 max-md:p-0 md:w-[38%]">
                                <a href="{{ route('institutional.unit', $unit) }}" class="link-draw font-semibold text-slate-900 hover:text-brand-700">{{ $unit->name }}</a>
                                <span class="mt-0.5 block text-xs text-slate-500">{{ $unit->type->getLabel() }}</span>
                            </td>
                            <td class="px-6 py-4 max-md:p-0">
                                <div class="flex items-center gap-3">
                                    <x-person-photo :src="$unit->head_photo_url" :name="$unit->head_name" class="size-11 rounded-full text-sm" />
                                    <div class="min-w-0">
                                        @if ($unit->head_name)
                                            <span class="block font-medium text-slate-900">{{ $unit->head_name }}</span>
                                        @else
                                            <span class="block text-slate-400">Sin designar</span>
                                        @endif
                                        @if ($unit->head_title)
                                            <span class="block text-xs text-slate-500">{{ $unit->head_title }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-600 max-md:p-0 max-md:pl-14">
                                @if ($unit->email)
                                    <a href="mailto:{{ $unit->email }}" class="link-draw block break-all hover:text-brand-700"><span class="sr-only">Correo: </span>{{ $unit->email }}</a>
                                @endif
                                @if ($unit->phone)
                                    <a href="tel:{{ $telephone($unit->phone) }}" class="link-draw block tabular-nums hover:text-brand-700"><span class="sr-only">Teléfono: </span>{{ $unit->phone }}</a>
                                @endif
                                @if (! $unit->email && ! $unit->phone)
                                    <span class="text-slate-400 max-md:hidden">Central de la entidad</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if (! empty($settings['phone_main']) || ! empty($settings['email']))
            <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-500">
                <p>Si una unidad no publica un contacto directo, comuníquese con la central:</p>
                @if (! empty($settings['phone_main']))
                    <a href="tel:{{ $telephone($settings['phone_main']) }}" class="inline-flex items-center gap-1.5 font-medium tabular-nums text-slate-700 hover:text-brand-700">
                        <x-heroicon-o-phone class="size-4 text-brand-600" /> <span class="link-draw">{{ $settings['phone_main'] }}</span>
                    </a>
                @endif
                @if (! empty($settings['email']))
                    <a href="mailto:{{ $settings['email'] }}" class="inline-flex items-center gap-1.5 font-medium text-slate-700 hover:text-brand-700">
                        <x-heroicon-o-envelope class="size-4 text-brand-600" /> <span class="link-draw">{{ $settings['email'] }}</span>
                    </a>
                @endif
            </div>
        @endif
    </div>
</x-layouts.app>
