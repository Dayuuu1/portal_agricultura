<x-layouts.app title="Información agraria" description="Boletines estadísticos, precios de mercado e información agraria de Madre de Dios.">
    <x-page-header title="Información agraria" subtitle="Estadística agrícola y pecuaria, boletines y precios de los mercados de Madre de Dios." :breadcrumbs="['Información agraria' => null]" />

    <div class="container-site mt-10 space-y-12">
        @forelse ($types as $type)
            <section aria-labelledby="tipo-{{ $type->id }}">
                <x-section-heading :title="$type->name" :link="route('documents.index', ['tipo' => $type->slug])" linkLabel="Ver todo por años" />
                @if ($type->description)
                    <p class="-mt-5 mb-6 text-slate-600">{{ $type->description }}</p>
                @endif
                @if ($type->documents->isNotEmpty())
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($type->documents as $document)
                            <a href="{{ route('documents.show', $document) }}" class="card card-link group flex items-start gap-4 p-5">
                                <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-gold-100 text-gold-600 ring-1 ring-gold-300/40 transition duration-300 ease-out-quint group-hover:-translate-y-0.5 group-hover:bg-gold-400 group-hover:text-brand-950">
                                    <x-heroicon-o-chart-bar class="size-6" />
                                </span>
                                <span>
                                    <span class="block font-semibold text-ink transition-colors group-hover:text-brand-700">{{ $document->title }}</span>
                                    @if ($document->issued_at)
                                        <span class="mt-1 block text-xs text-slate-500">{{ ucfirst($document->issued_at->translatedFormat('F Y')) }}</span>
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <x-empty-state icon="heroicon-o-chart-bar" title="Aún no hay publicaciones en esta sección" />
                @endif
            </section>
        @empty
            <x-empty-state icon="heroicon-o-chart-bar" title="No hay información agraria publicada" />
        @endforelse
    </div>
</x-layouts.app>
