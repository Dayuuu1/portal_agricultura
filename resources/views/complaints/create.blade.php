<x-layouts.app title="Libro de Reclamaciones" description="Registre su reclamo si no está conforme con la atención o el servicio recibido. Es gratuito y le responderemos en un plazo máximo de 30 días hábiles.">
    <x-page-header title="Libro de Reclamaciones"
        subtitle="Si no está conforme con la atención o el servicio que recibió, registre aquí su reclamo. Es gratuito y le responderemos en un plazo máximo de 30 días hábiles."
        :breadcrumbs="['Atención al ciudadano' => route('contact.create'), 'Libro de Reclamaciones' => null]" />

    @php
        $siteName = $settings['site_name'] ?? config('app.name');
        $describedBy = fn (string $field, bool $hint = false): string => trim(($hint ? "{$field}-hint " : '').($errors->has($field) ? "{$field}-error" : ''));
        $inputClass = fn (string $field): string => 'form-input'.($errors->has($field) ? ' ring-red-500' : '');
    @endphp

    <div class="container-site mt-10 grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @if ($errors->any())
                <div class="mb-6 flex items-start gap-3 rounded-xl bg-red-50 p-4 text-red-900 ring-1 ring-red-200" role="alert">
                    <x-heroicon-o-exclamation-triangle class="size-6 shrink-0 text-red-600" />
                    <p class="font-medium">Revise los campos marcados: hay {{ $errors->count() === 1 ? 'un dato' : $errors->count().' datos' }} por corregir.</p>
                </div>
            @endif

            <form method="POST" action="{{ route('complaints.store') }}" enctype="multipart/form-data" novalidate
                class="card overflow-hidden"
                x-data="{ docType: @js(old('document_type', 'dni')), isMinor: @js((bool) old('is_minor')), notify: @js((bool) old('notify_by_email')) }">
                @csrf
                <div class="hidden" aria-hidden="true">
                    <label for="website">No complete este campo</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                {{-- Cabecera de la hoja de reclamación --}}
                <div class="grid gap-4 border-b border-slate-200 bg-slate-50 p-6 sm:grid-cols-3 sm:p-8">
                    <div class="sm:col-span-2">
                        <p class="text-xs font-bold tracking-wider text-brand-700 uppercase">Hoja de reclamación virtual</p>
                        <p class="mt-1 font-bold text-slate-900">{{ $siteName }}</p>
                        @if (! empty($settings['address']))
                            <p class="text-sm text-slate-600">{{ $settings['address'] }}</p>
                        @endif
                    </div>
                    <dl class="space-y-1 text-sm sm:text-right">
                        <div><dt class="inline text-slate-500">N°:</dt> <dd class="inline font-semibold text-slate-800">se asigna al registrar</dd></div>
                        <div><dt class="inline text-slate-500">Fecha:</dt> <dd class="inline font-semibold text-slate-800">{{ now()->format('d/m/Y') }}</dd></div>
                    </dl>
                </div>

                <div class="space-y-10 p-6 sm:p-8">
                    <p class="text-sm text-slate-600">Los campos marcados con <span class="text-red-600">*</span> son obligatorios.</p>

                    {{-- 1. Persona --}}
                    <fieldset class="space-y-5">
                        <legend class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-700 text-sm text-white">1</span>
                            Identificación de la persona que presenta el reclamo
                        </legend>

                        <x-form-field for="full_name" label="Nombres y apellidos" :required="true">
                            <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" autocomplete="name" required maxlength="150"
                                class="{{ $inputClass('full_name') }}" @error('full_name') aria-invalid="true" @enderror aria-describedby="{{ $describedBy('full_name') }}">
                        </x-form-field>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form-field for="document_type" label="Tipo de documento" :required="true">
                                <select id="document_type" name="document_type" x-model="docType" required class="{{ $inputClass('document_type') }}">
                                    @foreach ($documentTypes as $type)
                                        <option value="{{ $type->value }}" @selected(old('document_type', 'dni') === $type->value)>{{ $type->getLabel() }}</option>
                                    @endforeach
                                </select>
                            </x-form-field>
                            <x-form-field for="document_number" label="Número de documento" :required="true">
                                <input type="text" id="document_number" name="document_number" value="{{ old('document_number') }}" required autocomplete="off"
                                    :inputmode="['dni', 'ruc'].includes(docType) ? 'numeric' : 'text'" :maxlength="docType === 'dni' ? 8 : (docType === 'ruc' ? 11 : 12)"
                                    class="{{ $inputClass('document_number') }}" @error('document_number') aria-invalid="true" @enderror aria-describedby="{{ $describedBy('document_number') }}">
                            </x-form-field>
                        </div>

                        <x-form-field for="address" label="Domicilio" :required="true" hint="Dirección, distrito y provincia. Allí se le notificará la respuesta si no autoriza el correo electrónico.">
                            <input type="text" id="address" name="address" value="{{ old('address') }}" autocomplete="street-address" required maxlength="255"
                                class="{{ $inputClass('address') }}" @error('address') aria-invalid="true" @enderror aria-describedby="{{ $describedBy('address', true) }}">
                        </x-form-field>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form-field for="phone" label="Teléfono celular" :required="true">
                                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" required maxlength="20"
                                    class="{{ $inputClass('phone') }}" @error('phone') aria-invalid="true" @enderror aria-describedby="{{ $describedBy('phone') }}">
                            </x-form-field>
                            <x-form-field for="email" label="Correo electrónico" required="notify" hint="Le enviaremos una copia de su reclamo.">
                                <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="150" :required="notify"
                                    class="{{ $inputClass('email') }}" @error('email') aria-invalid="true" @enderror aria-describedby="{{ $describedBy('email', true) }}">
                            </x-form-field>
                        </div>

                        <label class="flex items-start gap-2.5 text-sm text-slate-700">
                            <input type="checkbox" name="is_minor" value="1" x-model="isMinor" class="mt-0.5 rounded text-brand-600 focus:ring-brand-500">
                            <span>Soy menor de edad <span class="text-slate-500">(se registrarán también los datos de su padre, madre o apoderado)</span></span>
                        </label>

                        <div x-show="isMinor" x-cloak class="grid gap-5 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200 sm:grid-cols-2">
                            <x-form-field for="guardian_name" label="Nombre del padre, madre o apoderado" required="isMinor">
                                <input type="text" id="guardian_name" name="guardian_name" value="{{ old('guardian_name') }}" maxlength="150" :required="isMinor"
                                    class="{{ $inputClass('guardian_name') }}" @error('guardian_name') aria-invalid="true" @enderror aria-describedby="{{ $describedBy('guardian_name') }}">
                            </x-form-field>
                            <x-form-field for="guardian_document_number" label="DNI del padre, madre o apoderado" required="isMinor">
                                <input type="text" id="guardian_document_number" name="guardian_document_number" value="{{ old('guardian_document_number') }}" maxlength="20" :required="isMinor"
                                    class="{{ $inputClass('guardian_document_number') }}" @error('guardian_document_number') aria-invalid="true" @enderror aria-describedby="{{ $describedBy('guardian_document_number') }}">
                            </x-form-field>
                        </div>
                    </fieldset>

                    {{-- 2. Atención --}}
                    <fieldset class="space-y-5">
                        <legend class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-700 text-sm text-white">2</span>
                            Identificación de la atención brindada
                        </legend>

                        <div class="grid gap-5 sm:grid-cols-3">
                            <x-form-field for="incident_date" label="Fecha del hecho" :required="true">
                                <input type="date" id="incident_date" name="incident_date" value="{{ old('incident_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required
                                    class="{{ $inputClass('incident_date') }}" @error('incident_date') aria-invalid="true" @enderror aria-describedby="{{ $describedBy('incident_date') }}">
                            </x-form-field>
                            <x-form-field for="agency_id" label="Sede donde fue atendido">
                                <select id="agency_id" name="agency_id" class="{{ $inputClass('agency_id') }}">
                                    <option value="">Sede central (Puerto Maldonado)</option>
                                    @foreach ($agencies as $agency)
                                        <option value="{{ $agency->id }}" @selected((string) old('agency_id') === (string) $agency->id)>{{ $agency->name }}</option>
                                    @endforeach
                                </select>
                            </x-form-field>
                            <x-form-field for="organizational_unit_id" label="Oficina que lo atendió">
                                <select id="organizational_unit_id" name="organizational_unit_id" class="{{ $inputClass('organizational_unit_id') }}">
                                    <option value="">No lo sé / no aplica</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}" @selected((string) old('organizational_unit_id') === (string) $unit->id)>{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </x-form-field>
                        </div>

                        <fieldset>
                            <legend class="form-label">Motivo del reclamo <span class="text-red-600" aria-hidden="true">*</span></legend>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($reasons as $reason)
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-3.5 py-2.5 text-sm font-medium ring-1 ring-slate-300 has-checked:bg-brand-50 has-checked:text-brand-800 has-checked:ring-brand-500">
                                        <input type="radio" name="reason" value="{{ $reason->value }}" class="text-brand-600 focus:ring-brand-500" @checked(old('reason') === $reason->value)>
                                        {{ $reason->getLabel() }}
                                    </label>
                                @endforeach
                            </div>
                            @error('reason')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </fieldset>
                    </fieldset>

                    {{-- 3. Detalle --}}
                    <fieldset class="space-y-5">
                        <legend class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-700 text-sm text-white">3</span>
                            Detalle del reclamo
                        </legend>

                        <x-form-field for="description" label="Describa lo sucedido" :required="true" hint="Indique qué pasó, cuándo, dónde y, si lo sabe, quién lo atendió. Puede escribir en su lengua originaria.">
                            <textarea id="description" name="description" rows="7" required minlength="20" maxlength="4000"
                                class="{{ $inputClass('description') }}" @error('description') aria-invalid="true" @enderror aria-describedby="{{ $describedBy('description', true) }}">{{ old('description') }}</textarea>
                        </x-form-field>

                        <x-form-field for="request" label="¿Qué solicita a la entidad?" hint="Opcional. Por ejemplo: que se atienda su trámite, que se corrija un error, que se mejore la atención.">
                            <textarea id="request" name="request" rows="3" maxlength="2000"
                                class="{{ $inputClass('request') }}" @error('request') aria-invalid="true" @enderror aria-describedby="{{ $describedBy('request', true) }}">{{ old('request') }}</textarea>
                        </x-form-field>

                        <x-form-field for="attachments" label="Documentos de sustento" error="attachments" hint="Opcional. Hasta 3 archivos PDF, JPG o PNG de 5 MB cada uno. Su reclamo no será rechazado por no adjuntar documentos.">
                            <input type="file" id="attachments" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100"
                                aria-describedby="attachments-hint">
                        </x-form-field>
                        @foreach ($errors->get('attachments.*') as $messages)
                            <p class="-mt-3 text-sm text-red-600">{{ $messages[0] }}</p>
                        @endforeach
                    </fieldset>

                    {{-- 4. Notificación y declaración --}}
                    <fieldset class="space-y-4">
                        <legend class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-700 text-sm text-white">4</span>
                            Notificación de la respuesta
                        </legend>

                        <label class="flex items-start gap-2.5 rounded-xl bg-brand-50/60 p-4 text-sm text-slate-700 ring-1 ring-brand-100">
                            <input type="checkbox" name="notify_by_email" value="1" x-model="notify" class="mt-0.5 rounded text-brand-600 focus:ring-brand-500">
                            <span><strong>Autorizo que la respuesta a mi reclamo se me notifique al correo electrónico indicado.</strong>
                                Si no lo autoriza, la respuesta se notificará en el domicilio consignado.</span>
                        </label>

                        <label class="flex items-start gap-2.5 text-sm text-slate-700">
                            <input type="checkbox" name="truthful" value="1" class="mt-0.5 rounded text-brand-600 focus:ring-brand-500" @checked(old('truthful'))>
                            <span>Declaro que los datos consignados son verdaderos. <span class="text-red-600" aria-hidden="true">*</span></span>
                        </label>
                        @error('truthful')<p class="-mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

                        <label class="flex items-start gap-2.5 text-sm text-slate-700">
                            <input type="checkbox" name="privacy" value="1" class="mt-0.5 rounded text-brand-600 focus:ring-brand-500" @checked(old('privacy'))>
                            <span>Autorizo el tratamiento de mis datos personales para la atención de este reclamo, conforme a la Ley N° 29733 y la
                                <a href="{{ route('pages.show', 'politica-de-privacidad') }}" target="_blank" class="font-semibold text-brand-700 underline">política de privacidad</a>. <span class="text-red-600" aria-hidden="true">*</span></span>
                        </label>
                        @error('privacy')<p class="-mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </fieldset>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-500">Al registrar recibirá el número de su reclamo y una copia de la hoja de reclamación.</p>
                        <button type="submit" class="btn-primary shrink-0 px-6 py-3"><x-heroicon-o-book-open class="size-5" /> Registrar reclamo</button>
                    </div>
                </div>
            </form>
        </div>

        <aside class="space-y-6">
            <div class="card p-6">
                <h2 class="flex items-center gap-2 font-bold text-slate-900"><x-heroicon-o-information-circle class="size-5 text-brand-600" /> ¿Qué es un reclamo?</h2>
                <p class="mt-2 text-sm text-slate-600">Es la expresión de su insatisfacción o disconformidad con la atención o el servicio que le brindó la entidad: el trato recibido, la información, el tiempo de espera, el acceso al servicio o su resultado.</p>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-700">
                    <li class="flex gap-2.5"><x-heroicon-o-banknotes class="size-5 shrink-0 text-brand-600" /> <span>Es <strong>gratuito</strong>.</span></li>
                    <li class="flex gap-2.5"><x-heroicon-o-document-check class="size-5 shrink-0 text-brand-600" /> <span>Recibirá de inmediato una <strong>copia</strong> con el número de su reclamo.</span></li>
                    <li class="flex gap-2.5"><x-heroicon-o-clock class="size-5 shrink-0 text-brand-600" /> <span>Le responderemos en un plazo máximo de <strong>30 días hábiles</strong>.</span></li>
                    <li class="flex gap-2.5"><x-heroicon-o-pencil-square class="size-5 shrink-0 text-brand-600" /> <span>Si falta algún dato, le pediremos completarlo en 2 días hábiles.</span></li>
                </ul>
                <p class="mt-4 text-xs text-slate-500">Decreto Supremo N° 007-2020-PCM y Norma Técnica N° 001-2021-PCM-SGP.</p>
            </div>

            <div class="card space-y-3 p-6">
                <h2 class="font-bold text-slate-900">¿Su caso no es un reclamo?</h2>
                <a href="{{ route('contact.create') }}" class="flex items-start gap-3 rounded-lg p-2 hover:bg-brand-50">
                    <x-heroicon-o-chat-bubble-left-right class="size-5 shrink-0 text-brand-600" />
                    <span class="text-sm"><span class="block font-semibold text-slate-900">Consultas, sugerencias o felicitaciones</span><span class="text-slate-600">Escríbanos desde el formulario de contacto.</span></span>
                </a>
                <a href="{{ route('transparency') }}" class="flex items-start gap-3 rounded-lg p-2 hover:bg-brand-50">
                    <x-heroicon-o-eye class="size-5 shrink-0 text-brand-600" />
                    <span class="text-sm"><span class="block font-semibold text-slate-900">Acceso a la información pública</span><span class="text-slate-600">Se tramita conforme a la Ley de Transparencia.</span></span>
                </a>
                @if (! empty($settings['mesa_partes_url']))
                    <a href="{{ $settings['mesa_partes_url'] }}" target="_blank" rel="noopener" class="flex items-start gap-3 rounded-lg p-2 hover:bg-brand-50">
                        <x-heroicon-o-inbox-arrow-down class="size-5 shrink-0 text-brand-600" />
                        <span class="text-sm"><span class="block font-semibold text-slate-900">Trámites, quejas por defecto de tramitación y denuncias</span><span class="text-slate-600">Presente su escrito por mesa de partes.</span></span>
                    </a>
                @else
                    <p class="flex items-start gap-3 p-2 text-sm text-slate-600">
                        <x-heroicon-o-inbox-arrow-down class="size-5 shrink-0 text-brand-600" />
                        <span><span class="block font-semibold text-slate-900">Trámites, quejas por defecto de tramitación y denuncias</span>Preséntelos por mesa de partes en la sede central.</span>
                    </p>
                @endif
            </div>

            <div class="card p-6">
                <h2 class="flex items-center gap-2 font-bold text-slate-900"><x-heroicon-o-building-office-2 class="size-5 text-brand-600" /> Libro de Reclamaciones físico</h2>
                <p class="mt-2 text-sm text-slate-600">También puede solicitarlo en nuestra sede{{ ! empty($settings['address']) ? ': '.$settings['address'] : '' }}.</p>
                @if (! empty($settings['schedule']))
                    <p class="mt-2 flex gap-2 text-sm text-slate-700"><x-heroicon-o-clock class="size-5 shrink-0 text-brand-600" /> {{ $settings['schedule'] }}</p>
                @endif
                @if (! empty($settings['libro_reclamaciones_url']))
                    <a href="{{ $settings['libro_reclamaciones_url'] }}" target="_blank" rel="noopener" class="btn-secondary mt-4 w-full">
                        Plataforma del Estado (GOB.PE) <x-heroicon-o-arrow-top-right-on-square class="size-4" />
                    </a>
                @endif
            </div>
        </aside>
    </div>
</x-layouts.app>
