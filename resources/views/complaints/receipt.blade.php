<x-layouts.app title="Constancia de reclamo {{ $complaint->code }}" robots="noindex, nofollow">
    @php
        $siteName = $settings['site_name'] ?? config('app.name');
    @endphp

    <div class="container-site mt-10 max-w-4xl">
        <div class="mb-6 flex flex-col gap-4 rounded-2xl bg-brand-50 p-6 ring-1 ring-brand-200 sm:flex-row sm:items-center sm:justify-between print:hidden" role="status">
            <div class="flex items-start gap-3">
                <x-heroicon-o-check-circle class="size-8 shrink-0 text-brand-600" />
                <div>
                    <h1 class="text-xl font-extrabold text-brand-950">Su reclamo fue registrado</h1>
                    <p class="text-sm text-brand-900">
                        Guarde o imprima esta constancia.
                        @if (filled($complaint->email))
                            También enviamos una copia a <strong>{{ $complaint->email }}</strong>.
                        @endif
                    </p>
                </div>
            </div>
            <button type="button" onclick="window.print()" class="btn-primary shrink-0"><x-heroicon-o-printer class="size-5" /> Imprimir o guardar PDF</button>
        </div>

        <article class="card overflow-hidden print:shadow-none print:ring-0">
            <header class="grid gap-4 border-b border-slate-200 bg-slate-50 p-6 sm:grid-cols-3 sm:p-8">
                <div class="sm:col-span-2">
                    <p class="text-xs font-bold tracking-wider text-brand-700 uppercase">Libro de Reclamaciones · Hoja de reclamación</p>
                    <p class="mt-1 font-bold text-slate-900">{{ $siteName }}</p>
                    @if (! empty($settings['address']))
                        <p class="text-sm text-slate-600">{{ $settings['address'] }}</p>
                    @endif
                </div>
                <div class="sm:text-right">
                    <p class="text-xs text-slate-500">N° de reclamo</p>
                    <p class="font-display text-2xl font-extrabold text-brand-800">{{ $complaint->code }}</p>
                    <p class="text-sm text-slate-600">{{ $complaint->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </header>

            <div class="space-y-8 p-6 sm:p-8">
                <div class="rounded-xl bg-gold-50 p-4 text-sm text-slate-800 ring-1 ring-gold-300">
                    Plazo máximo de respuesta: <strong>{{ $complaint->due_on->translatedFormat('l j \d\e F \d\e Y') }}</strong>
                    ({{ \App\Models\Complaint::RESPONSE_DAYS }} días hábiles contados desde el día siguiente de su registro).
                    La respuesta se le notificará {{ $complaint->canBeNotifiedByEmail() ? 'al correo '.$complaint->email : 'en el domicilio indicado' }}.
                </div>

                @php
                    $sections = [
                        '1. Persona que presenta el reclamo' => array_filter([
                            'Nombres y apellidos' => $complaint->full_name,
                            'Documento' => $complaint->document_type->getLabel().' '.$complaint->document_number,
                            'Padre, madre o apoderado' => $complaint->is_minor ? $complaint->guardian_name.' ('.$complaint->guardian_document_number.')' : null,
                            'Domicilio' => $complaint->address,
                            'Teléfono' => $complaint->phone,
                            'Correo electrónico' => $complaint->email ?: '—',
                        ], fn ($value) => $value !== null),
                        '2. Atención brindada' => [
                            'Fecha del hecho' => $complaint->incident_date->format('d/m/Y'),
                            'Sede' => $complaint->placeLabel(),
                            'Oficina' => $complaint->organizationalUnit?->name ?? 'No precisa',
                            'Motivo' => $complaint->reason->getLabel(),
                        ],
                    ];
                @endphp

                @foreach ($sections as $heading => $rows)
                    <section>
                        <h2 class="mb-3 text-sm font-bold tracking-wide text-slate-900 uppercase">{{ $heading }}</h2>
                        <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                            @foreach ($rows as $label => $value)
                                <div>
                                    <dt class="text-slate-500">{{ $label }}</dt>
                                    <dd class="font-medium text-slate-900">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endforeach

                <section>
                    <h2 class="mb-3 text-sm font-bold tracking-wide text-slate-900 uppercase">3. Detalle del reclamo</h2>
                    <p class="text-sm whitespace-pre-line text-slate-800">{{ $complaint->description }}</p>
                    @if (filled($complaint->request))
                        <h3 class="mt-4 text-sm font-semibold text-slate-900">Pedido</h3>
                        <p class="text-sm whitespace-pre-line text-slate-800">{{ $complaint->request }}</p>
                    @endif
                    @if (! empty($complaint->attachments))
                        <h3 class="mt-4 text-sm font-semibold text-slate-900">Documentos adjuntos</h3>
                        <ul class="mt-1 list-inside list-disc text-sm text-slate-700">
                            @foreach ($complaint->attachments as $file)
                                <li>{{ $file['name'] }}</li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section>
                    <h2 class="mb-3 text-sm font-bold tracking-wide text-slate-900 uppercase">4. Notificación</h2>
                    <p class="text-sm text-slate-800">
                        {{ $complaint->notify_by_email ? 'Autorizó la notificación de la respuesta por correo electrónico.' : 'La respuesta se notificará en el domicilio consignado.' }}
                    </p>
                </section>

                <p class="border-t border-slate-200 pt-4 text-xs text-slate-500">
                    Registro gratuito conforme al Decreto Supremo N° 007-2020-PCM. Para consultar el estado de su reclamo, comuníquese
                    @if (! empty($settings['phone_main'])) al {{ $settings['phone_main'] }} @endif
                    @if (! empty($settings['complaints_email'])) o a {{ $settings['complaints_email'] }} @endif
                    indicando el número {{ $complaint->code }}.
                </p>
            </div>
        </article>

        <div class="mt-6 print:hidden">
            <a href="{{ route('home') }}" class="btn-secondary"><x-heroicon-o-arrow-left class="size-4" /> Volver al inicio</a>
        </div>
    </div>
</x-layouts.app>
