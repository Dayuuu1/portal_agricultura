@php
    use App\Enums\SubmissionFileKind;
    use App\Enums\SubmissionStatus;

    $responseFiles = $submission->filesOfKind(SubmissionFileKind::Respuesta);
    $daysLeft = $submission->workingDaysLeft();
@endphp

<x-mesa-partes.account-layout :title="'Expediente '.$submission->code" :subtitle="$submission->subject"
    :breadcrumbs="['Mi cuenta' => route('mesa-partes.account'), $submission->code => null]">

    <section class="card overflow-hidden" aria-labelledby="estado">
        <div class="flex flex-wrap items-start justify-between gap-4 p-6">
            <div class="min-w-0">
                <p class="text-sm text-slate-500">{{ $submission->procedureType->name }}</p>
                <h2 id="estado" class="mt-1 flex flex-wrap items-center gap-3 font-display text-2xl font-extrabold text-ink">
                    {{ $submission->code }}
                    <x-mesa-partes.status-badge :status="$submission->status" />
                </h2>
                <p class="mt-2 max-w-2xl text-slate-700">{{ $submission->status->description() }}</p>
            </div>
            <a href="{{ route('mesa-partes.submissions.receipt', $submission) }}" class="btn-secondary shrink-0"><x-heroicon-o-document-text class="size-5" /> Ver cargo</a>
        </div>
        <dl class="grid grid-cols-2 gap-px border-t border-slate-100 bg-slate-100 text-sm sm:grid-cols-4">
            <div class="bg-white p-4"><dt class="text-slate-500">Enviado</dt><dd class="font-semibold text-ink">{{ $submission->submitted_at->format('d/m/Y H:i') }}</dd></div>
            <div class="bg-white p-4"><dt class="text-slate-500">Fecha de presentación</dt><dd class="font-semibold text-ink">{{ $submission->filed_on->format('d/m/Y') }}</dd></div>
            <div class="bg-white p-4"><dt class="text-slate-500">Oficina</dt><dd class="font-semibold text-ink">{{ $submission->currentUnit?->name ?? 'Mesa de partes' }}</dd></div>
            <div class="bg-white p-4">
                <dt class="text-slate-500">{{ $submission->status === SubmissionStatus::Observado ? 'Subsanar hasta' : 'Plazo de atención' }}</dt>
                <dd class="font-semibold text-ink">
                    @if ($deadline = $submission->currentDeadline())
                        {{ $deadline->format('d/m/Y') }}
                        @if ($daysLeft !== null && $daysLeft >= 0)
                            <span class="block text-xs font-normal text-slate-500">{{ $daysLeft === 0 ? 'Vence hoy' : 'Quedan '.$daysLeft.' días hábiles' }}</span>
                        @endif
                    @else
                        —
                    @endif
                </dd>
            </div>
        </dl>
    </section>

    @if ($submission->status === SubmissionStatus::Observado)
        <section class="overflow-hidden rounded-2xl bg-white ring-2 ring-amber-300" aria-labelledby="observacion" x-data="{ open: @js($errors->any()) }">
            <div class="flex items-start gap-4 bg-amber-50 p-6">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-400 text-brand-950"><x-heroicon-o-exclamation-triangle class="size-6" /></span>
                <div class="min-w-0 flex-1">
                    <h2 id="observacion" class="font-display text-lg font-bold text-amber-950">Observación de la mesa de partes</h2>
                    <p class="mt-2 whitespace-pre-line text-amber-950">{{ $submission->observation }}</p>
                    <p class="mt-3 text-sm text-amber-900">
                        @if ($submission->canBeCured())
                            Tiene hasta el <strong>{{ $submission->cure_deadline->translatedFormat('l j \d\e F \d\e Y') }}</strong> para subsanarla. Si no lo hace, el documento se tendrá por no presentado (TUO de la Ley N° 27444, art. 136).
                        @else
                            El plazo para subsanarla venció el {{ $submission->cure_deadline->translatedFormat('j \d\e F \d\e Y') }}.
                        @endif
                    </p>
                </div>
            </div>

            @if ($submission->canBeCured())
                <div class="border-t border-amber-200 p-6">
                    <button type="button" class="btn-primary" x-show="! open" x-on:click="open = true"><x-heroicon-o-pencil-square class="size-5" /> Subsanar la observación</button>
                    <form method="POST" action="{{ route('mesa-partes.submissions.cure', $submission) }}" enctype="multipart/form-data" class="space-y-5" x-show="open" x-cloak novalidate>
                        @csrf
                        <x-form-field for="note" label="Qué corrigió o completó" :required="true">
                            <textarea id="note" name="note" rows="4" required maxlength="3000" class="form-input @error('note') ring-red-500 @enderror">{{ old('note') }}</textarea>
                        </x-form-field>
                        <div>
                            <label for="files" class="form-label">Documentos que adjunta <span class="font-normal text-slate-500">(opcional, hasta 5)</span></label>
                            <input type="file" id="files" name="files[]" multiple accept="application/pdf,image/jpeg,image/png,.pdf,.jpg,.jpeg,.png"
                                class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-white file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-800 file:ring-1 file:ring-brand-200 hover:file:bg-brand-50">
                            <p class="mt-1.5 text-xs text-slate-500">PDF, JPG o PNG de hasta {{ $maxFileMb }} MB cada uno.</p>
                            @foreach (collect($errors->get('files'))->merge($errors->get('files.*'))->flatten()->unique() as $message)
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @endforeach
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="btn-primary"><x-heroicon-o-paper-airplane class="size-5" /> Enviar subsanación</button>
                            <button type="button" class="btn-secondary" x-on:click="open = false">Cancelar</button>
                        </div>
                    </form>
                </div>
            @endif
        </section>
    @endif

    @if ($submission->status === SubmissionStatus::Atendido)
        <section class="overflow-hidden rounded-2xl bg-white ring-2 ring-emerald-300" aria-labelledby="respuesta">
            <div class="flex items-start gap-4 bg-emerald-50 p-6">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white"><x-heroicon-o-check-badge class="size-6" /></span>
                <div class="min-w-0 flex-1">
                    <h2 id="respuesta" class="font-display text-lg font-bold text-emerald-950">Respuesta de la entidad</h2>
                    <p class="text-sm text-emerald-900">Emitida el {{ $submission->answered_at->translatedFormat('j \d\e F \d\e Y, H:i') }}</p>
                    <p class="mt-3 whitespace-pre-line text-slate-800">{{ $submission->response }}</p>
                </div>
            </div>
            @if ($responseFiles->isNotEmpty())
                <ul class="divide-y divide-slate-100 border-t border-emerald-200">
                    @foreach ($responseFiles as $file)
                        <li class="flex items-center gap-4 px-6 py-3">
                            <x-file-type-tile :extension="$file->extension()" size="sm" />
                            <span class="min-w-0 flex-1 truncate text-sm font-semibold text-ink">{{ $file->original_name }}</span>
                            <a href="{{ route('mesa-partes.files.download', $file) }}" class="btn-primary"><x-heroicon-o-arrow-down-tray class="size-5" /> Descargar</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-5">
        <section class="card p-6 xl:col-span-3" aria-labelledby="hoja-de-ruta">
            <h2 id="hoja-de-ruta" class="font-display text-lg font-bold text-ink">Hoja de ruta</h2>
            <p class="mt-1 text-sm text-slate-600">Cada paso de su documento, con fecha y hora.</p>
            <x-mesa-partes.timeline :movements="$submission->movements" class="mt-6" />
        </section>

        <div class="space-y-6 xl:col-span-2">
            <section class="card p-6" aria-labelledby="datos">
                <h2 id="datos" class="font-display text-lg font-bold text-ink">Datos del documento</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-slate-500">Asunto</dt><dd class="font-semibold text-ink">{{ $submission->subject }}</dd></div>
                    <div><dt class="text-slate-500">Fundamentos</dt><dd class="whitespace-pre-line text-slate-700">{{ $submission->description }}</dd></div>
                    @if ($submission->targetUnit)
                        <div><dt class="text-slate-500">Dirigido a</dt><dd class="text-slate-700">{{ $submission->targetUnit->name }}</dd></div>
                    @endif
                    @if ($submission->related_code)
                        <div><dt class="text-slate-500">Expediente relacionado</dt><dd class="text-slate-700">{{ $submission->related_code }}</dd></div>
                    @endif
                    <div><dt class="text-slate-500">Folios</dt><dd class="text-slate-700">{{ $submission->folios }}</dd></div>
                </dl>
            </section>

            <section class="card overflow-hidden" aria-labelledby="archivos">
                <h2 id="archivos" class="px-6 pt-6 font-display text-lg font-bold text-ink">Archivos</h2>
                <ul class="mt-3 divide-y divide-slate-100">
                    @foreach ($submission->files as $file)
                        <li class="group relative flex items-center gap-3 px-6 py-3 hover:bg-brand-50/50">
                            <x-file-type-tile :extension="$file->extension()" size="sm" />
                            <span class="min-w-0 flex-1">
                                <a href="{{ route('mesa-partes.files.download', $file) }}" class="block truncate text-sm font-semibold text-ink after:absolute after:inset-0 group-hover:text-brand-700">{{ $file->original_name }}</a>
                                <span class="text-xs text-slate-500">{{ $file->kind->getLabel() }} · {{ $file->humanSize() }}</span>
                            </span>
                            <x-heroicon-o-arrow-down-tray class="size-5 shrink-0 text-slate-400 group-hover:text-brand-600" />
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
</x-mesa-partes.account-layout>
