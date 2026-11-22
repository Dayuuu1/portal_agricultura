<x-layouts.app title="Consulta de expediente" robots="noindex, nofollow">
    <x-page-header title="Consulta de expediente" subtitle="Estado y recorrido de un documento presentado en la Mesa de Partes Virtual."
        :breadcrumbs="['Mesa de partes virtual' => route('mesa-partes.index'), 'Consulta' => null]" />

    <div class="container-site mt-10 grid grid-cols-1 gap-8 lg:grid-cols-12">
        <form method="GET" action="{{ route('mesa-partes.lookup') }}" class="card h-fit space-y-4 p-6 lg:col-span-4">
            <x-form-field for="codigo" label="N° de expediente">
                <input type="text" id="codigo" name="codigo" value="{{ $code }}" class="form-input uppercase" maxlength="30" required autocomplete="off">
            </x-form-field>
            <x-form-field for="documento" label="Documento de quien lo presentó">
                <input type="text" id="documento" name="documento" value="{{ $document }}" class="form-input" maxlength="20" required autocomplete="off">
            </x-form-field>
            <button type="submit" class="btn-primary w-full"><x-heroicon-o-magnifying-glass class="size-5" /> Consultar</button>
            <p class="text-xs text-slate-500">Para ver los archivos y la respuesta, <a href="{{ route('mesa-partes.login') }}" class="font-semibold text-brand-700 hover:underline">ingrese a su cuenta</a>.</p>
        </form>

        <div class="lg:col-span-8" aria-live="polite">
            @if (! $searched)
                <div class="card p-8 text-slate-600">Ingrese el número de expediente que figura en su cargo y el número del documento de identidad de quien lo presentó.</div>
            @elseif (! $submission)
                <div class="flex items-start gap-3 rounded-2xl bg-amber-50 p-6 text-amber-900 ring-1 ring-amber-200" role="alert">
                    <x-heroicon-o-exclamation-triangle class="size-6 shrink-0" />
                    <p>No encontramos un expediente con esos datos. Revise el número del cargo y el documento de identidad.</p>
                </div>
            @else
                <article class="card overflow-hidden">
                    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 p-6">
                        <div>
                            <p class="text-sm text-slate-500">{{ $submission->procedureType->name }}</p>
                            <h2 class="font-display text-2xl font-extrabold text-ink">{{ $submission->code }}</h2>
                            <p class="mt-1 text-sm text-slate-600">Presentado el {{ $submission->filed_on->translatedFormat('j \d\e F \d\e Y') }}</p>
                        </div>
                        <x-mesa-partes.status-badge :status="$submission->status" />
                    </header>
                    <div class="space-y-6 p-6">
                        <p class="text-slate-700">{{ $submission->status->description() }}</p>
                        <x-mesa-partes.timeline :movements="$submission->movements" :with-notes="false" />
                    </div>
                </article>
            @endif
        </div>
    </div>
</x-layouts.app>
