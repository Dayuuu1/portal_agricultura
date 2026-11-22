@php
    use App\Support\MesaPartes\MesaPartesConfig;

    $siteName = $settings['site_name'] ?? config('app.name');
    $externalUrl = $settings['mesa_partes_url'] ?? null;
    $maxFileMb = MesaPartesConfig::maxFileKilobytes() / 1024;
    $maxTotalMb = MesaPartesConfig::maxTotalKilobytes() / 1024;
@endphp

<x-layouts.app title="Mesa de Partes Virtual" description="Presente solicitudes y documentos a la entidad en línea, las 24 horas, y siga su trámite desde su cuenta.">
    <x-page-header title="Mesa de Partes Virtual"
        subtitle="Presente sus solicitudes y documentos en línea, las 24 horas, y siga su trámite sin acudir a nuestras oficinas."
        :breadcrumbs="['Mesa de partes virtual' => null]" />

    <div class="container-site mt-10 space-y-8">
        <x-mesa-partes.flash />

        @if ($enabled)
            <div @class([
                'flex flex-col gap-3 rounded-2xl p-5 ring-1 sm:flex-row sm:items-center',
                'bg-brand-50 text-brand-950 ring-brand-200' => $withinHours,
                'bg-gold-50 text-slate-900 ring-gold-300' => ! $withinHours,
            ]) role="status">
                <span @class(['flex size-11 shrink-0 items-center justify-center rounded-xl', 'bg-brand-600 text-white' => $withinHours, 'bg-gold-400 text-brand-950' => ! $withinHours])>
                    <x-dynamic-component :component="$withinHours ? 'heroicon-o-check-badge' : 'heroicon-o-clock'" class="size-6" />
                </span>
                <p class="text-sm sm:text-base">
                    @if ($withinHours)
                        <strong>Recepción abierta.</strong> Lo que envíe ahora se considera presentado hoy, {{ now()->translatedFormat('l j \d\e F') }}.
                    @else
                        <strong>Fuera del horario de atención.</strong> Puede enviar su documento ahora: se considerará presentado el
                        <strong>{{ $nextFilingDate->translatedFormat('l j \d\e F') }}</strong>, primer día hábil siguiente (Ley N° 31170).
                    @endif
                </p>
            </div>
        @else
            <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 ring-1 ring-slate-200 sm:flex-row sm:items-start" role="status">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><x-heroicon-o-pause-circle class="size-6" /></span>
                <div class="space-y-2">
                    <h2 class="font-display text-lg font-bold text-ink">La Mesa de Partes Virtual todavía no recibe documentos</h2>
                    <p class="text-slate-600">
                        Mientras tanto, puede presentar sus documentos en la mesa de partes de la sede
                        @if (! empty($settings['address'])) ({{ $settings['address'] }}) @endif
                        @if (! empty($settings['schedule'])) en el horario {{ \Illuminate\Support\Str::lcfirst($settings['schedule']) }}@endif.
                    </p>
                    @if ($externalUrl)
                        <a href="{{ $externalUrl }}" target="_blank" rel="noopener" class="btn-primary mt-2">Ir a la mesa de partes en línea <x-heroicon-m-arrow-top-right-on-square class="size-4" /></a>
                    @endif
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
            <div class="min-w-0 space-y-12 lg:col-span-8">
                @if ($instructions = MesaPartesConfig::instructions())
                    <div class="rounded-2xl bg-white p-6 text-slate-700 ring-1 ring-slate-200">
                        <p class="whitespace-pre-line">{{ $instructions }}</p>
                    </div>
                @endif

                <section aria-labelledby="como-funciona">
                    <h2 id="como-funciona" class="font-display text-2xl font-bold text-ink">Cómo funciona</h2>
                    <ol class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        @foreach ([
                            ['Cree su cuenta', 'Registre sus datos y confirme su correo. Ahí recibirá las notificaciones de sus trámites.', 'heroicon-o-user-plus'],
                            ['Presente su documento', 'Elija el tipo de trámite, suba su escrito firmado en PDF y sus anexos. Recibirá su cargo al instante.', 'heroicon-o-document-arrow-up'],
                            ['Siga su trámite', 'Vea en qué oficina está, atienda las observaciones dentro del plazo y descargue la respuesta.', 'heroicon-o-map'],
                        ] as $index => [$step, $text, $icon])
                            <li class="card relative p-5">
                                <span class="absolute top-5 right-5 font-display text-3xl font-extrabold text-slate-100" aria-hidden="true">{{ $index + 1 }}</span>
                                <span class="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                                    <x-dynamic-component :component="$icon" class="size-6" />
                                </span>
                                <h3 class="mt-4 font-display text-lg font-bold text-ink"><span class="sr-only">Paso {{ $index + 1 }}: </span>{{ $step }}</h3>
                                <p class="mt-1.5 text-sm text-slate-600">{{ $text }}</p>
                            </li>
                        @endforeach
                    </ol>
                </section>

                <section aria-labelledby="que-presentar">
                    <h2 id="que-presentar" class="font-display text-2xl font-bold text-ink">Qué puede presentar</h2>
                    <p class="mt-2 text-slate-600">La entidad recibe todo documento que se le dirija. Si falta algún requisito, se le observará y tendrá {{ MesaPartesConfig::cureDays() }} días hábiles para subsanarlo.</p>

                    <ul class="mt-6 space-y-3">
                        @forelse ($types as $type)
                            <li>
                                <details class="card group overflow-hidden">
                                    <summary class="flex cursor-pointer list-none items-start gap-4 p-5 [&::-webkit-details-marker]:hidden">
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-heroicon-o-document-text class="size-5" /></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block font-semibold text-ink">{{ $type->name }}</span>
                                            @if ($type->description)
                                                <span class="mt-0.5 block text-sm text-slate-600">{{ $type->description }}</span>
                                            @endif
                                            <span class="mt-2 flex flex-wrap gap-2 text-xs">
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">
                                                    {{ $type->attentionDays() ? 'Se atiende en '.$type->attentionDays().' días hábiles' : 'Sin plazo propio' }}
                                                </span>
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">
                                                    {{ $type->fee ? 'Derecho de trámite: S/ '.number_format((float) $type->fee, 2) : 'Gratuito' }}
                                                </span>
                                            </span>
                                        </span>
                                        <x-heroicon-o-chevron-down class="mt-2 size-5 shrink-0 text-slate-400 transition group-open:rotate-180" />
                                    </summary>
                                    @if ($type->requirementList())
                                        <div class="border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                                            <p class="text-sm font-semibold text-slate-700">Requisitos</p>
                                            <ul class="mt-2 space-y-1.5 text-sm text-slate-700">
                                                @foreach ($type->requirementList() as $requirement)
                                                    <li class="flex gap-2"><x-heroicon-m-check class="mt-0.5 size-4 shrink-0 text-brand-600" />{{ $requirement }}</li>
                                                @endforeach
                                                @if ($type->requires_payment_proof)
                                                    <li class="flex gap-2"><x-heroicon-m-check class="mt-0.5 size-4 shrink-0 text-brand-600" />Comprobante del pago del derecho de trámite.</li>
                                                @endif
                                            </ul>
                                        </div>
                                    @endif
                                </details>
                            </li>
                        @empty
                            <li class="rounded-2xl bg-white p-6 text-slate-600 ring-1 ring-slate-200">La entidad aún no ha publicado los tipos de trámite.</li>
                        @endforelse
                    </ul>
                </section>

                <section aria-labelledby="archivos" class="card p-6 sm:p-8">
                    <h2 id="archivos" class="font-display text-xl font-bold text-ink">Antes de enviar, tenga listos sus archivos</h2>
                    <ul class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ([
                            ['Documento principal', "Su escrito en PDF, firmado y escaneado (o con firma digital). Máximo {$maxFileMb} MB.", 'heroicon-o-document-check'],
                            ['Anexos', 'PDF, JPG o PNG, hasta '.MesaPartesConfig::maxAttachments()." archivos de {$maxFileMb} MB cada uno.", 'heroicon-o-paper-clip'],
                            ['Tamaño total', "Todos los archivos de un trámite suman como máximo {$maxTotalMb} MB.", 'heroicon-o-scale'],
                            ['Declaración jurada', 'Al enviar declara que la información y los documentos son verdaderos (presunción de veracidad).', 'heroicon-o-shield-check'],
                        ] as [$label, $text, $icon])
                            <li class="flex gap-3">
                                <x-dynamic-component :component="$icon" class="size-6 shrink-0 text-brand-600" />
                                <span><span class="block font-semibold text-ink">{{ $label }}</span><span class="text-sm text-slate-600">{{ $text }}</span></span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                @if (MesaPartesConfig::legalBasis() || MesaPartesConfig::approval())
                    <section aria-labelledby="base-legal">
                        <h2 id="base-legal" class="font-display text-xl font-bold text-ink">Base legal</h2>
                        <ul class="mt-4 space-y-2 text-sm text-slate-700">
                            @if ($approval = MesaPartesConfig::approval())
                                <li class="flex gap-2"><x-heroicon-o-check-badge class="size-5 shrink-0 text-brand-600" /><span><strong>Aprobada por:</strong> {{ $approval }}</span></li>
                            @endif
                            @foreach (MesaPartesConfig::legalBasis() as $norm)
                                <li class="flex gap-2"><x-heroicon-o-scale class="size-5 shrink-0 text-slate-400" />{{ $norm }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            <aside class="lg:col-span-4">
                <div class="space-y-6 lg:sticky lg:top-24">
                    <div class="rounded-2xl bg-brand-900 p-6 text-brand-100">
                        @if ($citizen)
                            <h2 class="font-display text-lg font-bold text-white">Hola, {{ $citizen->fullName() }}</h2>
                            <p class="mt-1 text-sm">Continúe con sus trámites desde su cuenta.</p>
                            <div class="mt-5 grid gap-2">
                                @if ($enabled)
                                    <a href="{{ route('mesa-partes.submissions.create') }}" class="btn-gold w-full"><x-heroicon-o-document-plus class="size-5" /> Presentar documento</a>
                                @endif
                                <a href="{{ route('mesa-partes.account') }}" class="btn-ghost-light w-full">Ir a mis trámites</a>
                            </div>
                        @else
                            <h2 class="font-display text-lg font-bold text-white">Ingrese a su cuenta</h2>
                            <p class="mt-1 text-sm">Necesita una cuenta para presentar documentos y recibir notificaciones.</p>
                            <div class="mt-5 grid gap-2">
                                <a href="{{ route('mesa-partes.login') }}" class="btn-gold w-full">Ingresar</a>
                                @if ($enabled)
                                    <a href="{{ route('mesa-partes.register') }}" class="btn-ghost-light w-full">Crear una cuenta</a>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if (MesaPartesConfig::publicLookup())
                        <form method="GET" action="{{ route('mesa-partes.lookup') }}" class="card space-y-4 p-6" aria-labelledby="consulta-titulo">
                            <div>
                                <h2 id="consulta-titulo" class="font-display text-lg font-bold text-ink">Consulte un expediente</h2>
                                <p class="mt-1 text-sm text-slate-600">Sin ingresar a su cuenta, con el número del cargo.</p>
                            </div>
                            <x-form-field for="codigo" label="N° de expediente">
                                <input type="text" id="codigo" name="codigo" placeholder="{{ MesaPartesConfig::codePrefix() }}-{{ now()->year }}-000001" class="form-input uppercase" maxlength="30" required autocomplete="off">
                            </x-form-field>
                            <x-form-field for="documento" label="Documento de quien lo presentó">
                                <input type="text" id="documento" name="documento" class="form-input" maxlength="20" required autocomplete="off" inputmode="numeric">
                            </x-form-field>
                            <button type="submit" class="btn-secondary w-full"><x-heroicon-o-magnifying-glass class="size-5" /> Consultar</button>
                        </form>
                    @endif

                    <div class="card space-y-3 p-6 text-sm text-slate-700">
                        <h2 class="font-display text-lg font-bold text-ink">Horario para el cómputo de plazos</h2>
                        <p class="flex gap-2"><x-heroicon-o-clock class="size-5 shrink-0 text-brand-600" />{{ MesaPartesConfig::scheduleLabel() }}</p>
                        <p class="text-slate-600">Lo que llegue fuera de ese horario o en día no hábil se considera presentado el siguiente día hábil.</p>
                        @if (! empty($settings['email']))
                            <p class="flex gap-2"><x-heroicon-o-envelope class="size-5 shrink-0 text-brand-600" /><a href="mailto:{{ $settings['email'] }}" class="font-semibold text-brand-700 hover:underline">{{ $settings['email'] }}</a></p>
                        @endif
                        @if (! empty($settings['phone_main']))
                            <p class="flex gap-2"><x-heroicon-o-phone class="size-5 shrink-0 text-brand-600" />{{ $settings['phone_main'] }}</p>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.app>
