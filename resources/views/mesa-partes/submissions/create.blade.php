@php
    $stepFields = [
        1 => ['procedure_type_id'],
        2 => ['subject', 'description', 'related_code', 'target_unit_id'],
        3 => ['principal', 'attachments', 'attachments.*', 'payment_proof'],
        4 => ['truthful'],
    ];
    $initialStep = collect($stepFields)->search(fn (array $fields): bool => collect($errors->keys())->contains(fn (string $key): bool => in_array(preg_replace('/\.\d+$/', '.*', $key), $fields, true))) ?: 1;
    $inputClass = fn (string $field): string => 'form-input'.($errors->has($field) ? ' ring-red-500' : '');
    $typeData = $types->mapWithKeys(fn ($type) => [$type->id => [
        'name' => $type->name,
        'days' => $type->attentionDays(),
        'proof' => $type->requires_payment_proof,
    ]]);
    $maxBytes = (int) ($maxFileMb * 1024 * 1024);
    $steps = ['Tipo de trámite', 'Detalle', 'Archivos', 'Revisión y envío'];
@endphp

<x-mesa-partes.account-layout title="Presentar documento" subtitle="Complete los cuatro pasos. Al enviar recibirá su cargo de recepción con el número de expediente."
    :breadcrumbs="['Mi cuenta' => route('mesa-partes.account'), 'Presentar documento' => null]">

    @if ($errors->any())
        <div class="flex items-start gap-3 rounded-xl bg-red-50 p-4 text-red-900 ring-1 ring-red-200" role="alert">
            <x-heroicon-o-exclamation-triangle class="size-6 shrink-0 text-red-600" />
            <p class="font-medium">Revise los datos marcados. Por seguridad, vuelva a seleccionar los archivos antes de enviar.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('mesa-partes.submissions.store') }}" enctype="multipart/form-data" novalidate class="card overflow-hidden"
        x-data="{
            step: {{ $initialStep }},
            types: @js($typeData),
            typeId: @js((string) old('procedure_type_id', '')),
            subject: @js(old('subject', '')),
            principal: null,
            attachments: [],
            proof: null,
            sizeError: '',
            maxBytes: {{ $maxBytes }},
            get type() { return this.types[this.typeId] ?? null },
            formatSize(bytes) { return bytes > 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB' },
            pick(event, target) {
                const files = Array.from(event.target.files);
                const tooBig = files.find(file => file.size > this.maxBytes);
                this.sizeError = tooBig ? `«${tooBig.name}» pesa ${this.formatSize(tooBig.size)} y supera el máximo de {{ $maxFileMb }} MB.` : '';
                if (tooBig) { event.target.value = ''; }
                const picked = tooBig ? [] : files.map(file => ({ name: file.name, size: this.formatSize(file.size) }));
                if (target === 'attachments') { this.attachments = picked } else { this[target] = picked[0] ?? null }
            },
            next() {
                const fields = Array.from(this.$refs['step' + this.step].querySelectorAll('input, select, textarea'));
                const invalid = fields.find(field => ! field.disabled && ! field.checkValidity());
                if (invalid) { invalid.reportValidity(); return; }
                this.step++;
                this.$nextTick(() => this.$refs.steps.scrollIntoView({ behavior: 'smooth', block: 'start' }));
            },
        }">
        @csrf

        <ol x-ref="steps" class="grid grid-cols-2 gap-px border-b border-slate-200 bg-slate-200 sm:grid-cols-4" aria-label="Pasos">
            @foreach ($steps as $index => $label)
                @php($number = $index + 1)
                <li class="bg-white">
                    <button type="button" class="flex w-full items-center gap-3 px-4 py-3.5 text-left text-sm transition-colors disabled:cursor-default"
                        x-on:click="if ({{ $number }} < step) step = {{ $number }}" x-bind:disabled="{{ $number }} >= step"
                        x-bind:aria-current="step === {{ $number }} ? 'step' : null">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold transition-colors"
                            x-bind:class="step > {{ $number }} ? 'bg-brand-600 text-white' : (step === {{ $number }} ? 'bg-brand-700 text-white ring-4 ring-brand-100' : 'bg-slate-100 text-slate-500')">
                            <span x-show="step <= {{ $number }}">{{ $number }}</span>
                            <x-heroicon-m-check class="size-4" x-show="step > {{ $number }}" x-cloak />
                        </span>
                        <span class="font-semibold" x-bind:class="step === {{ $number }} ? 'text-ink' : 'text-slate-500'">{{ $label }}</span>
                    </button>
                </li>
            @endforeach
        </ol>

        <div class="p-6 sm:p-8">
            {{-- Paso 1 --}}
            <section x-ref="step1" x-show="step === 1" aria-labelledby="paso-1">
                <h2 id="paso-1" class="font-display text-xl font-bold text-ink">¿Qué desea presentar?</h2>
                <p class="mt-1 text-sm text-slate-600">Si su caso no encaja en un tipo específico, elija «{{ $types->first()?->name ?? 'Solicitud o escrito' }}».</p>
                <div class="mt-5 grid gap-3" role="radiogroup">
                    @foreach ($types as $type)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl p-4 ring-1 ring-slate-200 transition hover:ring-brand-300 has-checked:bg-brand-50 has-checked:ring-2 has-checked:ring-brand-600">
                            <input type="radio" name="procedure_type_id" value="{{ $type->id }}" x-model="typeId" required class="mt-1 text-brand-600 focus:ring-brand-500">
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold text-ink">{{ $type->name }}</span>
                                @if ($type->description)<span class="mt-0.5 block text-sm text-slate-600">{{ $type->description }}</span>@endif
                                <span class="mt-2 flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-full bg-white px-2.5 py-1 font-semibold text-slate-700 ring-1 ring-slate-200">{{ $type->attentionDays() ? $type->attentionDays().' días hábiles' : 'Sin plazo propio' }}</span>
                                    @if ($type->fee)<span class="rounded-full bg-white px-2.5 py-1 font-semibold text-slate-700 ring-1 ring-slate-200">S/ {{ number_format((float) $type->fee, 2) }}</span>@endif
                                </span>
                                @if ($type->requirementList())
                                    <ul class="mt-3 space-y-1 text-sm text-slate-600">
                                        @foreach ($type->requirementList() as $requirement)
                                            <li class="flex gap-2"><x-heroicon-m-check class="mt-0.5 size-4 shrink-0 text-brand-600" />{{ $requirement }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('procedure_type_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </section>

            {{-- Paso 2 --}}
            <section x-ref="step2" x-show="step === 2" x-cloak class="space-y-5" aria-labelledby="paso-2">
                <h2 id="paso-2" class="font-display text-xl font-bold text-ink">Detalle de su documento</h2>
                <x-form-field for="subject" label="Asunto" :required="true" hint="Una frase que resuma su pedido. Ej.: «Solicito constancia de productor agrario».">
                    <input type="text" id="subject" name="subject" x-model="subject" required maxlength="200" class="{{ $inputClass('subject') }}">
                </x-form-field>
                <x-form-field for="description" label="Fundamentos o descripción" :required="true" hint="Explique brevemente lo que solicita y por qué. El detalle completo va en su documento principal.">
                    <textarea id="description" name="description" rows="5" required minlength="10" maxlength="5000" class="{{ $inputClass('description') }}">{{ old('description') }}</textarea>
                </x-form-field>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form-field for="target_unit_id" label="Oficina a la que se dirige" hint="Opcional. Si no la conoce, la mesa de partes la derivará.">
                        <select id="target_unit_id" name="target_unit_id" class="{{ $inputClass('target_unit_id') }}">
                            <option value="">No sé / que la mesa de partes decida</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected((string) old('target_unit_id') === (string) $unit->id)>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </x-form-field>
                    <x-form-field for="related_code" label="Expediente relacionado" hint="Opcional. Si su documento complementa un trámite anterior, indique su número.">
                        <input type="text" id="related_code" name="related_code" value="{{ old('related_code') }}" maxlength="60" class="{{ $inputClass('related_code') }}">
                    </x-form-field>
                </div>
            </section>

            {{-- Paso 3 --}}
            <section x-ref="step3" x-show="step === 3" x-cloak class="space-y-6" aria-labelledby="paso-3">
                <div>
                    <h2 id="paso-3" class="font-display text-xl font-bold text-ink">Archivos</h2>
                    <p class="mt-1 text-sm text-slate-600">Máximo {{ $maxFileMb }} MB por archivo y {{ $maxTotalMb }} MB en total.</p>
                </div>

                <p x-show="sizeError" x-text="sizeError" x-cloak class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200" role="alert"></p>

                <div class="rounded-2xl border-2 border-dashed border-slate-300 p-5 transition has-[input:focus-visible]:border-brand-500 hover:border-brand-400">
                    <label for="principal" class="flex items-center gap-2 font-semibold text-ink"><x-heroicon-o-document-check class="size-5 text-brand-600" /> Documento principal <span class="text-red-600" aria-hidden="true">*</span></label>
                    <p class="mt-1 text-sm text-slate-600">Su escrito en PDF, firmado y escaneado (o con firma digital).</p>
                    <input type="file" id="principal" name="principal" accept="application/pdf,.pdf" required x-on:change="pick($event, 'principal')"
                        class="mt-3 block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-brand-600 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-700">
                    @error('principal')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="rounded-2xl border-2 border-dashed border-slate-300 p-5 transition hover:border-brand-400">
                    <label for="attachments" class="flex items-center gap-2 font-semibold text-ink"><x-heroicon-o-paper-clip class="size-5 text-brand-600" /> Anexos <span class="font-normal text-slate-500">(opcional)</span></label>
                    <p class="mt-1 text-sm text-slate-600">PDF, JPG o PNG. Hasta {{ $maxAttachments }} archivos. Puede seleccionar varios a la vez.</p>
                    <input type="file" id="attachments" name="attachments[]" multiple accept="application/pdf,image/jpeg,image/png,.pdf,.jpg,.jpeg,.png" x-on:change="pick($event, 'attachments')"
                        class="mt-3 block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-white file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-800 file:ring-1 file:ring-brand-200 hover:file:bg-brand-50">
                    <ul x-show="attachments.length" x-cloak class="mt-3 space-y-1 text-sm text-slate-700">
                        <template x-for="file in attachments" :key="file.name">
                            <li class="flex items-center gap-2"><x-heroicon-o-paper-clip class="size-4 text-slate-400" /><span x-text="file.name" class="truncate"></span><span x-text="file.size" class="text-xs text-slate-500"></span></li>
                        </template>
                    </ul>
                    @error('attachments')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    @foreach (collect($errors->get('attachments.*'))->flatten()->unique() as $message)
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @endforeach
                </div>

                <div x-show="type && type.proof" x-cloak class="rounded-2xl border-2 border-dashed border-gold-400 bg-gold-50/40 p-5">
                    <label for="payment_proof" class="flex items-center gap-2 font-semibold text-ink"><x-heroicon-o-banknotes class="size-5 text-gold-600" /> Comprobante de pago <span class="text-red-600" aria-hidden="true">*</span></label>
                    <p class="mt-1 text-sm text-slate-600">Este trámite tiene derecho de pago. Adjunte el voucher o constancia (PDF, JPG o PNG).</p>
                    <input type="file" id="payment_proof" name="payment_proof" accept="application/pdf,image/jpeg,image/png,.pdf,.jpg,.jpeg,.png" x-bind:required="type && type.proof" x-bind:disabled="! (type && type.proof)" x-on:change="pick($event, 'proof')"
                        class="mt-3 block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-white file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-800 file:ring-1 file:ring-brand-200">
                    @error('payment_proof')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </section>

            {{-- Paso 4 --}}
            <section x-ref="step4" x-show="step === 4" x-cloak class="space-y-6" aria-labelledby="paso-4">
                <h2 id="paso-4" class="font-display text-xl font-bold text-ink">Revise y envíe</h2>
                <dl class="grid grid-cols-1 gap-4 rounded-2xl bg-slate-50 p-5 text-sm ring-1 ring-slate-200 sm:grid-cols-2">
                    <div><dt class="text-slate-500">Tipo de trámite</dt><dd class="font-semibold text-ink" x-text="type ? type.name : '—'"></dd></div>
                    <div><dt class="text-slate-500">Plazo de atención</dt><dd class="font-semibold text-ink" x-text="type && type.days ? type.days + ' días hábiles' : 'Sin plazo propio'"></dd></div>
                    <div class="sm:col-span-2"><dt class="text-slate-500">Asunto</dt><dd class="font-semibold text-ink" x-text="subject || '—'"></dd></div>
                    <div class="sm:col-span-2"><dt class="text-slate-500">Archivos</dt>
                        <dd class="font-semibold text-ink" x-text="(principal ? principal.name : 'Falta el documento principal') + (attachments.length ? ' + ' + attachments.length + (attachments.length === 1 ? ' anexo' : ' anexos') : '') + (proof ? ' + comprobante' : '')"></dd>
                    </div>
                </dl>

                <div @class(['flex items-start gap-3 rounded-xl p-4 text-sm ring-1', 'bg-brand-50 text-brand-950 ring-brand-200' => $withinHours, 'bg-gold-50 text-slate-900 ring-gold-300' => ! $withinHours])>
                    <x-heroicon-o-calendar-days class="size-5 shrink-0" />
                    <p>
                        @if ($withinHours)
                            Si lo envía ahora, se considerará presentado <strong>hoy, {{ now()->translatedFormat('j \d\e F') }}</strong>.
                        @else
                            Está fuera del horario de atención: se considerará presentado el <strong>{{ $nextFilingDate->translatedFormat('l j \d\e F') }}</strong> (Ley N° 31170).
                        @endif
                    </p>
                </div>

                <label class="flex items-start gap-3 rounded-xl p-4 text-sm text-slate-700 ring-1 ring-slate-200">
                    <input type="checkbox" name="truthful" value="1" required class="mt-0.5 rounded text-brand-600 focus:ring-brand-500">
                    <span><strong class="text-ink">Declaración jurada.</strong> Declaro que la información y los documentos que presento son verdaderos y auténticos, y me someto a las responsabilidades de ley en caso de falsedad (TUO de la Ley N° 27444, principio de presunción de veracidad).</span>
                </label>
                @error('truthful')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </section>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:px-8">
            <button type="button" class="btn-secondary" x-show="step > 1" x-cloak x-on:click="step--"><x-heroicon-m-arrow-left class="size-4" /> Anterior</button>
            <span x-show="step === 1"></span>
            <button type="button" class="btn-primary" x-show="step < 4" x-on:click="next()">Siguiente <x-heroicon-m-arrow-right class="size-4" /></button>
            <button type="submit" class="btn-primary" x-show="step === 4" x-cloak><x-heroicon-o-paper-airplane class="size-5" /> Enviar documento</button>
        </div>
    </form>
</x-mesa-partes.account-layout>
