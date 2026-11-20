<x-layouts.app title="Transparencia" description="Información pública: documentos de gestión, planeamiento, presupuesto, contrataciones y control institucional.">
    <x-page-header title="Transparencia y acceso a la información pública" subtitle="En cumplimiento del TUO de la Ley N° 27806, Ley de Transparencia y Acceso a la Información Pública." :breadcrumbs="['Transparencia' => null]" />

    <div class="container-site mt-10 space-y-12">
        <section class="grid gap-4 md:grid-cols-3">
            @if (! empty($settings['transparencia_url']))
                <a href="{{ $settings['transparencia_url'] }}" target="_blank" rel="noopener" class="card card-link group flex items-start gap-4 bg-brand-900 p-6 text-white ring-0 hover:bg-brand-800">
                    <x-heroicon-o-eye class="size-8 shrink-0 text-gold-300" />
                    <span>
                        <span class="block text-lg font-bold">Portal de Transparencia Estándar</span>
                        <span class="text-sm text-brand-100">Información de presupuesto, personal y contrataciones en la plataforma del Estado.</span>
                    </span>
                </a>
            @else
                <div class="card flex items-start gap-4 bg-amber-50 p-6 ring-amber-200">
                    <x-heroicon-o-information-circle class="size-8 shrink-0 text-amber-600" />
                    <p class="text-sm text-amber-900">El enlace al Portal de Transparencia Estándar (PTE) se configura desde el panel de administración.</p>
                </div>
            @endif
            <a href="{{ route('job-postings.index') }}" class="card card-link group flex items-start gap-4 p-6">
                <x-heroicon-o-briefcase class="size-8 shrink-0 text-brand-600" />
                <span>
                    <span class="block text-lg font-bold text-slate-900 group-hover:text-brand-700">Convocatorias de personal</span>
                    <span class="text-sm text-slate-600">Procesos CAS, bases y resultados.</span>
                </span>
            </a>
            <a href="{{ route('contact.create') }}" class="card card-link group flex items-start gap-4 p-6">
                <x-heroicon-o-chat-bubble-left-right class="size-8 shrink-0 text-brand-600" />
                <span>
                    <span class="block text-lg font-bold text-slate-900 group-hover:text-brand-700">Solicitud de información</span>
                    <span class="text-sm text-slate-600">Canales de atención y consultas ciudadanas.</span>
                </span>
            </a>
        </section>

        <section>
            <x-section-heading eyebrow="Documentos" title="Información institucional publicada" />
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($types as $type)
                    <div class="card flex flex-col p-6">
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <h3 class="font-bold text-slate-900">{{ $type->name }}</h3>
                            <span class="shrink-0 rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">{{ $type->documents_count }}</span>
                        </div>
                        <ul class="mb-4 space-y-2 text-sm">
                            @forelse ($type->documents as $document)
                                <li class="flex gap-2">
                                    <x-heroicon-o-document-text class="mt-0.5 size-4 shrink-0 text-slate-400" />
                                    <a href="{{ route('documents.show', $document) }}" class="line-clamp-2 text-slate-700 hover:text-brand-700">{{ $document->title }}</a>
                                </li>
                            @empty
                                <li class="text-slate-500">Sin documentos publicados.</li>
                            @endforelse
                        </ul>
                        <a href="{{ route('documents.index', ['tipo' => $type->slug]) }}" class="mt-auto inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-900">
                            Ver todos <x-heroicon-o-arrow-right class="size-4" />
                        </a>
                    </div>
                @endforeach
            </div>
        </section>

        @if ($stateLinks->isNotEmpty())
            <section>
                <x-section-heading eyebrow="Consulta ciudadana" title="Enlaces del Estado" />
                <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($stateLinks as $link)
                        <li>
                            <a href="{{ $link->url }}" target="_blank" rel="noopener" class="card card-link group flex h-full items-center justify-between gap-3 p-4">
                                <span>
                                    <span class="block font-bold text-slate-900">{{ $link->name }}</span>
                                    <span class="block text-xs text-slate-500">{{ $link->description }}</span>
                                </span>
                                <x-heroicon-o-arrow-top-right-on-square class="size-4 shrink-0 text-brand-600" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.app>
