<x-layouts.app :title="$jobPosting->code" :description="$jobPosting->title" :schema="\App\Support\Seo\StructuredData::jobPosting($jobPosting)">
    <x-page-header :title="$jobPosting->code" :subtitle="$jobPosting->title" :breadcrumbs="['Convocatorias' => route('job-postings.index'), $jobPosting->code => null]">
        <div class="mt-5">
            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold ring-1 ring-inset {{ $jobPosting->status->badgeClasses() }}">
                {{ $jobPosting->status->getLabel() }}
            </span>
        </div>
    </x-page-header>

    <div class="container-site mt-10 grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card p-6">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900"><x-heroicon-o-document-arrow-down class="size-6 text-brand-600" /> Documentos del proceso</h2>
                @if ($jobPosting->files->isNotEmpty())
                    <ol class="relative space-y-4 border-l-2 border-brand-100 pl-6">
                        @foreach ($jobPosting->files as $file)
                            <li class="relative">
                                <span class="absolute top-1.5 -left-[1.95rem] size-3.5 rounded-full border-2 border-white bg-brand-500 ring-2 ring-brand-100"></span>
                                <div class="flex flex-col gap-2 rounded-xl bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $file->label }}</p>
                                        @if ($file->published_at)
                                            <p class="text-xs text-slate-500">Publicado el {{ $file->published_at->translatedFormat('j \d\e F \d\e Y') }}</p>
                                        @endif
                                    </div>
                                    @if ($file->file_url)
                                        <a href="{{ $file->file_url }}" target="_blank" rel="noopener" class="btn-secondary shrink-0 py-2">
                                            <x-heroicon-o-arrow-down-tray class="size-4" /> Descargar
                                        </a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-sm text-slate-500">Aún no se han publicado documentos para esta convocatoria.</p>
                @endif
            </div>

            @if ($jobPosting->description)
                <div class="card p-6">
                    <h2 class="mb-3 text-lg font-bold text-slate-900">Descripción</h2>
                    <div class="prose prose-slate max-w-none">{!! str($jobPosting->description)->sanitizeHtml() !!}</div>
                </div>
            @endif
        </div>

        <aside>
            <div class="card p-6">
                <h2 class="mb-4 font-bold text-slate-900">Datos de la convocatoria</h2>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-slate-500">Modalidad</dt><dd class="font-semibold text-slate-900">{{ $jobPosting->modality }}</dd></div>
                    <div><dt class="text-slate-500">Puestos</dt><dd class="font-semibold text-slate-900">{{ $jobPosting->positions_count }}</dd></div>
                    @if ($jobPosting->starts_at)
                        <div><dt class="text-slate-500">Inicio</dt><dd class="font-semibold text-slate-900">{{ $jobPosting->starts_at->translatedFormat('j \d\e F \d\e Y') }}</dd></div>
                    @endif
                    @if ($jobPosting->ends_at)
                        <div><dt class="text-slate-500">Cierre</dt><dd class="font-semibold text-slate-900">{{ $jobPosting->ends_at->translatedFormat('j \d\e F \d\e Y') }}</dd></div>
                    @endif
                    @if ($jobPosting->organizationalUnit)
                        <div><dt class="text-slate-500">Unidad responsable</dt><dd class="font-semibold text-slate-900">{{ $jobPosting->organizationalUnit->name }}</dd></div>
                    @endif
                </dl>
                <a href="{{ route('job-postings.index') }}" class="btn-secondary mt-6 w-full"><x-heroicon-o-chevron-left class="size-4" /> Todas las convocatorias</a>
            </div>
        </aside>
    </div>
</x-layouts.app>
