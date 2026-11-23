<x-layouts.app :title="$unit->name" :description="$unit->description">
    <x-page-header :title="$unit->name" :subtitle="$unit->type->getLabel()" :breadcrumbs="array_filter([
        'Organigrama' => route('institutional.organization-chart'),
        $unit->parent?->name ?? '' => $unit->parent ? route('institutional.unit', $unit->parent) : null,
        $unit->name => null,
    ], fn ($url, $label) => $label !== '', ARRAY_FILTER_USE_BOTH)" />

    @php
        $functions = $unit->functions_html;
        $hasContent = filled($unit->description) || filled(strip_tags($functions)) || $unit->children->isNotEmpty() || $documents->isNotEmpty();
        // Un anexo escrito después del número no forma parte de lo que se marca.
        $telephone = fn (string $phone): string => preg_replace('/[^\d+]/', '', preg_split('/\b(?:anexo|anx|ext)\b/iu', $phone)[0]);
    @endphp

    <div class="container-site mt-10 grid grid-cols-1 gap-8 lg:grid-cols-3 lg:gap-10">
        <div class="space-y-8 lg:col-span-2">
            @if ($unit->description)
                <p class="max-w-3xl text-[1.0625rem] leading-relaxed text-slate-700 sm:text-lg sm:leading-relaxed">{{ $unit->description }}</p>
            @endif

            @if (filled(strip_tags($functions)))
                <section class="card p-6 sm:p-8" aria-labelledby="funciones">
                    <h2 id="funciones" class="mb-4 text-xl font-bold text-ink">Funciones</h2>
                    <div class="prose prose-slate max-w-none [--tw-prose-bullets:var(--color-gold-500)] prose-p:my-3 prose-li:my-2.5 prose-li:pl-1 prose-ul:my-4">{!! $functions !!}</div>
                </section>
            @endif

            @if ($unit->children->isNotEmpty())
                <section aria-labelledby="unidades">
                    <h2 id="unidades" class="mb-4 text-xl font-bold text-ink">Unidades a su cargo</h2>
                    <ul class="grid gap-3 sm:grid-cols-2">
                        @foreach ($unit->children as $child)
                            <li>
                                <a href="{{ route('institutional.unit', $child) }}" class="card card-link group flex h-full items-center gap-4 p-4">
                                    @if ($child->head_name)
                                        <x-person-photo :src="$child->head_photo_url" :name="$child->head_name" class="size-12 rounded-full text-sm" />
                                    @else
                                        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                                            <x-heroicon-o-building-office class="size-5" />
                                        </span>
                                    @endif
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-semibold text-slate-900 group-hover:text-brand-700">{{ $child->name }}</span>
                                        @if ($child->head_name)
                                            <span class="mt-0.5 block text-sm text-slate-500">{{ $child->head_name }}</span>
                                        @endif
                                    </span>
                                    <x-heroicon-m-chevron-right class="size-5 shrink-0 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-brand-600" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($documents->isNotEmpty())
                <section aria-labelledby="documentos">
                    <h2 id="documentos" class="mb-4 text-xl font-bold text-ink">Documentos publicados</h2>
                    <ul class="card divide-y divide-slate-100 overflow-hidden">
                        @foreach ($documents as $document)
                            <x-document-item :document="$document" />
                        @endforeach
                    </ul>
                </section>
            @endif

            @unless ($hasContent)
                <x-empty-state icon="heroicon-o-document-text" title="Ficha en preparación" message="La presentación y las funciones de esta unidad aún no se han publicado." />
            @endunless
        </div>

        <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start" aria-label="Responsable y contacto">
            <section class="card overflow-hidden">
                @if ($unit->head_name)
                    <div class="flex items-center gap-5 p-6">
                        <x-person-photo :src="$unit->head_photo_url" :name="$unit->head_name" class="size-24 rounded-2xl text-2xl" />
                        <div class="min-w-0">
                            <h2 class="text-sm font-semibold text-brand-700">{{ $unit->head_title ?: 'Responsable' }}</h2>
                            <p class="mt-1 font-display text-xl leading-snug font-bold text-ink">{{ $unit->head_name }}</p>
                        </div>
                    </div>
                @endif

                <div @class(['p-6', 'border-t border-slate-100 bg-slate-50/60' => $unit->head_name])>
                    @if ($unit->email || $unit->phone)
                        <h2 class="mb-3 text-sm font-semibold text-slate-900">Contacto de la unidad</h2>
                        <ul class="space-y-2.5 text-sm">
                            @if ($unit->email)
                                <li class="flex items-start gap-2.5">
                                    <x-heroicon-o-envelope class="mt-0.5 size-4 shrink-0 text-brand-600" />
                                    <a href="mailto:{{ $unit->email }}" class="link-draw break-all text-slate-700 hover:text-brand-700"><span class="sr-only">Correo: </span>{{ $unit->email }}</a>
                                </li>
                            @endif
                            @if ($unit->phone)
                                <li class="flex items-start gap-2.5">
                                    <x-heroicon-o-phone class="mt-0.5 size-4 shrink-0 text-brand-600" />
                                    <a href="tel:{{ $telephone($unit->phone) }}" class="link-draw tabular-nums text-slate-700 hover:text-brand-700"><span class="sr-only">Teléfono: </span>{{ $unit->phone }}</a>
                                </li>
                            @endif
                        </ul>
                    @else
                        <h2 class="mb-1 text-sm font-semibold text-slate-900">Atención al ciudadano</h2>
                        <p class="mb-3 text-sm text-slate-500">Esta unidad atiende a través de la central de la entidad.</p>
                        <ul class="space-y-2.5 text-sm">
                            @if (! empty($settings['phone_main']))
                                <li class="flex items-start gap-2.5">
                                    <x-heroicon-o-phone class="mt-0.5 size-4 shrink-0 text-brand-600" />
                                    <a href="tel:{{ $telephone($settings['phone_main']) }}" class="link-draw tabular-nums text-slate-700 hover:text-brand-700"><span class="sr-only">Teléfono de la central: </span>{{ $settings['phone_main'] }}</a>
                                </li>
                            @endif
                            @if (! empty($settings['email']))
                                <li class="flex items-start gap-2.5">
                                    <x-heroicon-o-envelope class="mt-0.5 size-4 shrink-0 text-brand-600" />
                                    <a href="mailto:{{ $settings['email'] }}" class="link-draw break-all text-slate-700 hover:text-brand-700"><span class="sr-only">Correo: </span>{{ $settings['email'] }}</a>
                                </li>
                            @endif
                            @if (! empty($settings['schedule']))
                                <li class="flex items-start gap-2.5">
                                    <x-heroicon-o-clock class="mt-0.5 size-4 shrink-0 text-brand-600" />
                                    <span class="text-slate-700">{{ $settings['schedule'] }}</span>
                                </li>
                            @endif
                        </ul>
                    @endif
                </div>
            </section>

            @if ($unit->parent)
                <a href="{{ route('institutional.unit', $unit->parent) }}" class="card card-link group flex items-center gap-4 p-5">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                        <x-heroicon-o-building-office-2 class="size-5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm text-slate-500">Depende de</span>
                        <span class="block font-semibold text-slate-900 group-hover:text-brand-700">{{ $unit->parent->name }}</span>
                    </span>
                    <x-heroicon-m-chevron-right class="size-5 shrink-0 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-brand-600" />
                </a>
            @endif

            @if (\App\Support\MesaPartes\MesaPartesConfig::enabled())
                <section class="relative isolate overflow-hidden rounded-2xl bg-brand-900 p-6 text-white">
                    <x-site.contours class="absolute inset-0 -z-10 size-full text-brand-300/20" />
                    <h2 class="font-display text-lg font-bold">¿Necesita presentar un documento?</h2>
                    <p class="mt-1 text-sm text-brand-100">Envíelo por la mesa de partes virtual; se deriva a la unidad que corresponde.</p>
                    <a href="{{ route('mesa-partes.index') }}" class="btn-gold mt-4">Ir a la mesa de partes</a>
                </section>
            @endif
        </aside>
    </div>
</x-layouts.app>
