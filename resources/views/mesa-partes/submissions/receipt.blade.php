@php
    $siteName = $settings['site_name'] ?? config('app.name');
    $citizen = $submission->citizen;
    $filedLater = ! $submission->filed_on->isSameDay($submission->submitted_at);
@endphp

<x-layouts.app :title="'Cargo de recepción '.$submission->code" robots="noindex, nofollow">
    <div class="container-site mt-10 max-w-4xl">
        <div class="mb-6 flex flex-col gap-4 rounded-2xl bg-brand-50 p-6 ring-1 ring-brand-200 sm:flex-row sm:items-center sm:justify-between print:hidden">
            <div class="flex items-start gap-3">
                <x-heroicon-o-check-circle class="size-8 shrink-0 text-brand-600" />
                <div>
                    <h1 class="text-xl font-extrabold text-brand-950">Cargo de recepción</h1>
                    <p class="text-sm text-brand-900">Guárdelo o imprímalo. También le enviamos una copia a <strong>{{ $citizen->email }}</strong>.</p>
                </div>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <button type="button" onclick="window.print()" class="btn-primary"><x-heroicon-o-printer class="size-5" /> Imprimir o guardar PDF</button>
                <a href="{{ route('mesa-partes.submissions.show', $submission) }}" class="btn-secondary">Ver mi trámite</a>
            </div>
        </div>

        <article class="card overflow-hidden print:shadow-none print:ring-0">
            <header class="grid grid-cols-1 gap-4 border-b border-slate-200 bg-slate-50 p-6 sm:grid-cols-3 sm:p-8">
                <div class="sm:col-span-2">
                    <p class="text-sm font-semibold text-brand-700">Mesa de Partes Virtual · Cargo de recepción</p>
                    <p class="mt-1 font-bold text-slate-900">{{ $siteName }}</p>
                    @if (! empty($settings['address']))
                        <p class="text-sm text-slate-600">{{ $settings['address'] }}</p>
                    @endif
                </div>
                <div class="sm:text-right">
                    <p class="text-xs text-slate-500">N° de expediente</p>
                    <p class="font-display text-2xl font-extrabold text-brand-800">{{ $submission->code }}</p>
                </div>
            </header>

            <div class="space-y-8 p-6 sm:p-8">
                <dl class="grid grid-cols-1 gap-4 rounded-xl bg-gold-50 p-5 text-sm ring-1 ring-gold-300 sm:grid-cols-3">
                    <div><dt class="text-slate-600">Enviado el</dt><dd class="font-bold text-slate-900">{{ $submission->submitted_at->format('d/m/Y H:i:s') }}</dd></div>
                    <div><dt class="text-slate-600">Fecha de presentación</dt><dd class="font-bold text-slate-900">{{ $submission->filed_on->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-slate-600">Folios</dt><dd class="font-bold text-slate-900">{{ $submission->folios }}</dd></div>
                    @if ($filedLater)
                        <div class="sm:col-span-3"><dt class="sr-only">Nota</dt><dd class="text-slate-700">Se envió en día u hora no hábil: se considera presentado el primer día hábil siguiente (Ley N° 31170).</dd></div>
                    @endif
                </dl>

                <section>
                    <h2 class="text-sm font-bold text-brand-800">Administrado</h2>
                    <dl class="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-slate-500">{{ $citizen->person_type->getLabel() }}</dt><dd class="font-semibold text-slate-900">{{ $citizen->fullName() }}</dd></div>
                        <div><dt class="text-slate-500">Documento</dt><dd class="font-semibold text-slate-900">{{ $citizen->documentLabel() }}</dd></div>
                        @if ($citizen->representative_name)
                            <div><dt class="text-slate-500">Representante legal</dt><dd class="text-slate-900">{{ $citizen->representative_name }}</dd></div>
                        @endif
                        <div><dt class="text-slate-500">Domicilio</dt><dd class="text-slate-900">{{ $citizen->address }}, {{ $citizen->district }}, {{ $citizen->province }}, {{ $citizen->department }}</dd></div>
                        <div><dt class="text-slate-500">Notificación electrónica</dt><dd class="text-slate-900">{{ $citizen->acceptsElectronicNotifications() ? 'Autorizada · '.$citizen->email : 'No autorizada (se notifica en el domicilio)' }}</dd></div>
                    </dl>
                </section>

                <section>
                    <h2 class="text-sm font-bold text-brand-800">Documento</h2>
                    <dl class="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-slate-500">Tipo de trámite</dt><dd class="font-semibold text-slate-900">{{ $submission->procedureType->name }}</dd></div>
                        <div><dt class="text-slate-500">Plazo de atención</dt><dd class="text-slate-900">{{ $submission->due_on ? 'Hasta el '.$submission->due_on->format('d/m/Y') : 'Sin plazo propio' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-slate-500">Asunto</dt><dd class="font-semibold text-slate-900">{{ $submission->subject }}</dd></div>
                        @if ($submission->targetUnit)
                            <div><dt class="text-slate-500">Dirigido a</dt><dd class="text-slate-900">{{ $submission->targetUnit->name }}</dd></div>
                        @endif
                        @if ($submission->related_code)
                            <div><dt class="text-slate-500">Expediente relacionado</dt><dd class="text-slate-900">{{ $submission->related_code }}</dd></div>
                        @endif
                    </dl>
                </section>

                <section>
                    <h2 class="text-sm font-bold text-brand-800">Archivos recibidos</h2>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="text-xs text-slate-500">
                                <tr class="border-b border-slate-200"><th scope="col" class="py-2 pr-4 font-medium">Archivo</th><th scope="col" class="py-2 pr-4 font-medium">Tipo</th><th scope="col" class="py-2 pr-4 font-medium">Folios</th><th scope="col" class="py-2 font-medium">Huella SHA-256</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($submission->files as $file)
                                    <tr>
                                        <td class="py-2 pr-4 font-semibold text-slate-900">{{ $file->original_name }}</td>
                                        <td class="py-2 pr-4 text-slate-600">{{ $file->kind->getLabel() }}</td>
                                        <td class="py-2 pr-4 text-slate-600">{{ $file->pages }}</td>
                                        <td class="py-2 font-mono text-[0.7rem] break-all text-slate-500">{{ $file->sha256 }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <p class="border-t border-slate-200 pt-6 text-xs text-slate-500">
                    La recepción no implica la conformidad con lo solicitado. Si falta algún requisito, se le observará y tendrá un plazo para subsanarlo (TUO de la Ley N° 27444, art. 136).
                    La huella SHA-256 permite comprobar que los archivos recibidos son los mismos que usted envió.
                </p>
            </div>
        </article>
    </div>
</x-layouts.app>
