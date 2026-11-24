@php($isAgrarian = $document->documentType->isAgrarianInformation())

<x-layouts.app :title="$document->title" :description="$document->description ?? $document->title" :nav="$isAgrarian ? 'informacion' : null">
    <x-page-header :title="$document->title" :breadcrumbs="[
        ($isAgrarian ? 'Información agraria' : 'Documentos') => $isAgrarian ? route('agrarian-information') : route('documents.index'),
        $document->documentType->name => $document->documentType->publicUrl(),
        'Detalle' => null,
    ]" />

    <div class="container-site mt-10 grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if ($document->file_path && $document->file_extension === 'PDF')
                <div class="card overflow-hidden">
                    <object data="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($document->file_path) }}#view=FitH" type="application/pdf" class="h-[75vh] w-full" aria-label="Vista previa del documento">
                        <div class="p-8 text-center text-sm text-slate-600">
                            Su navegador no puede mostrar la vista previa.
                            <a href="{{ route('documents.download', $document) }}" class="font-semibold text-brand-700 underline">Descargue el documento</a>.
                        </div>
                    </object>
                </div>
            @endif

            @if ($document->description)
                <div class="card p-6">
                    <h2 class="mb-2 font-bold text-slate-900">Descripción</h2>
                    <p class="text-slate-700">{{ $document->description }}</p>
                </div>
            @endif
        </div>

        <aside class="space-y-6">
            <div class="card p-6">
                <h2 class="mb-4 font-bold text-slate-900">Ficha del documento</h2>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-slate-500">Tipo</dt><dd class="font-semibold text-slate-900">{{ $document->documentType->name }}</dd></div>
                    @if ($document->code)
                        <div><dt class="text-slate-500">Número / código</dt><dd class="font-semibold text-slate-900">{{ $document->code }}</dd></div>
                    @endif
                    @if ($document->issued_at)
                        <div><dt class="text-slate-500">Fecha de emisión</dt><dd class="font-semibold text-slate-900">{{ $document->issued_at->translatedFormat('j \d\e F \d\e Y') }}</dd></div>
                    @endif
                    @if ($document->organizationalUnit)
                        <div><dt class="text-slate-500">Órgano / unidad</dt><dd><a href="{{ route('institutional.unit', $document->organizationalUnit) }}" class="font-semibold text-brand-700 hover:underline">{{ $document->organizationalUnit->name }}</a></dd></div>
                    @endif
                    @if ($document->file_extension)
                        <div><dt class="text-slate-500">Formato</dt><dd class="font-semibold text-slate-900">{{ $document->file_extension }}@if ($document->human_file_size) · {{ $document->human_file_size }}@endif</dd></div>
                    @endif
                    <div><dt class="text-slate-500">Publicado en el portal</dt><dd class="font-semibold text-slate-900">{{ $document->created_at->format('d/m/Y') }}</dd></div>
                </dl>
                @if ($document->hasFile())
                    <a href="{{ route('documents.download', $document) }}" class="btn-primary mt-6 w-full">
                        <x-heroicon-o-arrow-down-tray class="size-5" /> {{ $document->file_path ? 'Descargar documento' : 'Abrir documento' }}
                    </a>
                @else
                    <p class="mt-6 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">El archivo de este documento aún no está disponible.</p>
                @endif
            </div>

            @if ($related->isNotEmpty())
                <div class="card p-6">
                    <h2 class="mb-4 font-bold text-slate-900">Más en {{ $document->documentType->name }}</h2>
                    <ul class="space-y-3">
                        @foreach ($related as $item)
                            <li><a href="{{ route('documents.show', $item) }}" class="text-sm font-medium text-slate-700 hover:text-brand-700">{{ $item->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
</x-layouts.app>
