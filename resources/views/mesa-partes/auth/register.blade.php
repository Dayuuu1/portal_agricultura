@php
    $inputClass = fn (string $field): string => 'form-input'.($errors->has($field) ? ' ring-red-500' : '');
    $invalid = fn (string $field): string => $errors->has($field) ? 'aria-invalid=true aria-describedby='.$field.'-error' : '';
    $legend = 'mb-4 flex items-center gap-2 text-lg font-bold text-ink';
    $number = 'flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-700 text-sm text-white';
@endphp

<x-layouts.app title="Crear cuenta en la Mesa de Partes Virtual" robots="noindex, nofollow">
    <x-page-header title="Crear una cuenta" subtitle="Con su cuenta podrá presentar documentos, recibir su cargo y las notificaciones de sus trámites."
        :breadcrumbs="['Mesa de partes virtual' => route('mesa-partes.index'), 'Crear cuenta' => null]" />

    <div class="container-site mt-10 grid grid-cols-1 gap-8 lg:grid-cols-12">
        <div class="lg:col-span-8">
            @if ($errors->any())
                <div class="mb-6 flex items-start gap-3 rounded-xl bg-red-50 p-4 text-red-900 ring-1 ring-red-200" role="alert">
                    <x-heroicon-o-exclamation-triangle class="size-6 shrink-0 text-red-600" />
                    <p class="font-medium">Revise los campos marcados: hay {{ $errors->count() === 1 ? 'un dato' : $errors->count().' datos' }} por corregir.</p>
                </div>
            @endif

            <form method="POST" action="{{ route('mesa-partes.register.store') }}" class="card space-y-10 p-6 sm:p-8" novalidate
                x-data="{ personType: @js(old('person_type', 'natural')), docType: @js(old('document_type', 'dni')) }"
                x-effect="if (personType === 'juridica') { docType = 'ruc' } else if (docType === 'ruc') { docType = 'dni' }">
                @csrf
                <div class="hidden" aria-hidden="true">
                    <label for="website">No complete este campo</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>
                <p class="text-sm text-slate-600">Todos los campos son obligatorios, salvo que se indique lo contrario.</p>

                <fieldset>
                    <legend class="{{ $legend }}"><span class="{{ $number }}">1</span> ¿Quién presentará los documentos?</legend>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach (\App\Enums\PersonType::cases() as $type)
                            @continue($type === \App\Enums\PersonType::Juridica && ! $allowsLegalEntities)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl p-4 ring-1 transition has-checked:bg-brand-50 has-checked:ring-2 has-checked:ring-brand-600 ring-slate-200 hover:ring-brand-300">
                                <input type="radio" name="person_type" value="{{ $type->value }}" x-model="personType" class="mt-1 text-brand-600 focus:ring-brand-500">
                                <span>
                                    <span class="block font-semibold text-ink">{{ $type->getLabel() }}</span>
                                    <span class="block text-sm text-slate-600">{{ $type === \App\Enums\PersonType::Natural ? 'Presenta a nombre propio, con DNI, carné de extranjería o pasaporte.' : 'Empresa, asociación u organización, con RUC y representante legal.' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="space-y-5">
                    <legend class="{{ $legend }}"><span class="{{ $number }}">2</span> Identificación</legend>

                    {{-- Sent while the select is disabled (legal entities always use RUC). --}}
                    <input type="hidden" name="document_type" x-bind:value="docType">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-form-field for="document_type" label="Tipo de documento" x-show="personType === 'natural'">
                            <select id="document_type" name="document_type" x-model="docType" x-bind:disabled="personType === 'juridica'" class="{{ $inputClass('document_type') }}">
                                @foreach ($documentTypes as $type)
                                    @continue($type === \App\Enums\IdentityDocumentType::Ruc)
                                    <option value="{{ $type->value }}">{{ $type->getLabel() }}</option>
                                @endforeach
                            </select>
                        </x-form-field>
                        <x-form-field for="document_number" label="Número de documento">
                            <input type="text" id="document_number" name="document_number" value="{{ old('document_number') }}" required autocomplete="off"
                                x-bind:inputmode="['dni', 'ruc'].includes(docType) ? 'numeric' : 'text'" x-bind:maxlength="docType === 'dni' ? 8 : (docType === 'ruc' ? 11 : 12)"
                                x-bind:placeholder="docType === 'ruc' ? 'RUC de 11 dígitos' : (docType === 'dni' ? '8 dígitos' : '')"
                                class="{{ $inputClass('document_number') }}" {{ $invalid('document_number') }}>
                        </x-form-field>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2" x-show="personType === 'natural'">
                        <x-form-field for="first_name" label="Nombres">
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" maxlength="100" autocomplete="given-name" class="{{ $inputClass('first_name') }}" {{ $invalid('first_name') }}>
                        </x-form-field>
                        <x-form-field for="last_name" label="Apellidos">
                            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" maxlength="100" autocomplete="family-name" class="{{ $inputClass('last_name') }}" {{ $invalid('last_name') }}>
                        </x-form-field>
                    </div>

                    <div class="space-y-5" x-show="personType === 'juridica'" x-cloak>
                        <x-form-field for="business_name" label="Razón social">
                            <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" maxlength="200" autocomplete="organization" class="{{ $inputClass('business_name') }}" {{ $invalid('business_name') }}>
                        </x-form-field>
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-form-field for="representative_name" label="Representante legal" hint="Nombres y apellidos de quien presenta en nombre de la organización.">
                                <input type="text" id="representative_name" name="representative_name" value="{{ old('representative_name') }}" maxlength="150" class="{{ $inputClass('representative_name') }}" {{ $invalid('representative_name') }}>
                            </x-form-field>
                            <x-form-field for="representative_document_number" label="Documento del representante">
                                <input type="text" id="representative_document_number" name="representative_document_number" value="{{ old('representative_document_number') }}" maxlength="12" class="{{ $inputClass('representative_document_number') }}" {{ $invalid('representative_document_number') }}>
                            </x-form-field>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="space-y-5">
                    <legend class="{{ $legend }}"><span class="{{ $number }}">3</span> Contacto y domicilio</legend>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-form-field for="email" label="Correo electrónico" hint="Le enviaremos un enlace para confirmarlo. Con él ingresará a su cuenta.">
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="150" autocomplete="email" class="{{ $inputClass('email') }}" {{ $invalid('email') }}>
                        </x-form-field>
                        <x-form-field for="phone" label="Teléfono celular">
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required maxlength="20" autocomplete="tel" inputmode="tel" class="{{ $inputClass('phone') }}" {{ $invalid('phone') }}>
                        </x-form-field>
                    </div>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <x-form-field for="department" label="Departamento">
                            <select id="department" name="department" required class="{{ $inputClass('department') }}">
                                <option value="">Seleccione</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department }}" @selected(old('department', 'Madre de Dios') === $department)>{{ $department }}</option>
                                @endforeach
                            </select>
                        </x-form-field>
                        <x-form-field for="province" label="Provincia">
                            <input type="text" id="province" name="province" value="{{ old('province') }}" required maxlength="80" class="{{ $inputClass('province') }}" {{ $invalid('province') }}>
                        </x-form-field>
                        <x-form-field for="district" label="Distrito">
                            <input type="text" id="district" name="district" value="{{ old('district') }}" required maxlength="80" class="{{ $inputClass('district') }}" {{ $invalid('district') }}>
                        </x-form-field>
                    </div>
                    <x-form-field for="address" label="Dirección" hint="Calle, número, urbanización o referencia. Sirve como domicilio para las notificaciones que no sean electrónicas.">
                        <input type="text" id="address" name="address" value="{{ old('address') }}" required maxlength="255" autocomplete="street-address" class="{{ $inputClass('address') }}" {{ $invalid('address') }}>
                    </x-form-field>
                </fieldset>

                <fieldset class="space-y-5">
                    <legend class="{{ $legend }}"><span class="{{ $number }}">4</span> Contraseña</legend>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-form-field for="password" label="Contraseña" hint="Al menos 10 caracteres, con mayúsculas, minúsculas y números.">
                            <input type="password" id="password" name="password" required autocomplete="new-password" class="{{ $inputClass('password') }}" {{ $invalid('password') }}>
                        </x-form-field>
                        <x-form-field for="password_confirmation" label="Repita la contraseña">
                            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="form-input">
                        </x-form-field>
                    </div>
                </fieldset>

                <fieldset class="space-y-4">
                    <legend class="{{ $legend }}"><span class="{{ $number }}">5</span> Autorizaciones</legend>
                    @include('mesa-partes.partials.privacy-notice')

                    <label class="flex items-start gap-3 text-sm text-slate-700">
                        <input type="checkbox" name="privacy" value="1" @checked(old('privacy')) class="mt-0.5 rounded text-brand-600 focus:ring-brand-500">
                        <span>Autorizo el tratamiento de mis datos personales para los fines descritos en el aviso de privacidad.</span>
                    </label>
                    @error('privacy')<p id="privacy-error" class="text-sm text-red-600">{{ $message }}</p>@enderror

                    <label class="flex items-start gap-3 text-sm text-slate-700">
                        <input type="checkbox" name="truthful" value="1" @checked(old('truthful')) class="mt-0.5 rounded text-brand-600 focus:ring-brand-500">
                        <span>Declaro que los datos que registro son verdaderos (principio de presunción de veracidad, TUO de la Ley N° 27444).</span>
                    </label>
                    @error('truthful')<p id="truthful-error" class="text-sm text-red-600">{{ $message }}</p>@enderror

                    <label class="flex items-start gap-3 rounded-xl bg-brand-50/60 p-4 text-sm text-slate-700 ring-1 ring-brand-200">
                        <input type="checkbox" name="e_notifications" value="1" @checked(old('e_notifications', true)) class="mt-0.5 rounded text-brand-600 focus:ring-brand-500">
                        <span>
                            <span class="block font-semibold text-ink">Autorizo que me notifiquen por medios electrónicos <span class="font-normal text-slate-500">(opcional)</span></span>
                            Las observaciones y respuestas se dejarán en la bandeja de mi cuenta y se enviarán a mi correo (TUO de la Ley N° 27444, art. 20.4).
                            Si no lo autoriza, se le notificará en su domicilio. Puede cambiar esta opción en su perfil.
                        </span>
                    </label>
                </fieldset>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-600">¿Ya tiene una cuenta? <a href="{{ route('mesa-partes.login') }}" class="font-semibold text-brand-700 hover:underline">Ingrese aquí</a></p>
                    <button type="submit" class="btn-primary">Crear mi cuenta</button>
                </div>
            </form>
        </div>

        <aside class="lg:col-span-4">
            <div class="space-y-4 lg:sticky lg:top-24">
                <div class="rounded-2xl bg-brand-900 p-6 text-brand-100">
                    <h2 class="font-display text-lg font-bold text-white">Qué sigue después</h2>
                    <ol class="mt-4 space-y-3 text-sm">
                        <li class="flex gap-3"><span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-gold-400 text-xs font-bold text-brand-950">1</span>Confirme su correo con el enlace que le enviaremos.</li>
                        <li class="flex gap-3"><span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-gold-400 text-xs font-bold text-brand-950">2</span>Presente su documento en PDF y reciba su cargo.</li>
                        <li class="flex gap-3"><span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-gold-400 text-xs font-bold text-brand-950">3</span>Siga el trámite y atienda las observaciones desde su cuenta.</li>
                    </ol>
                </div>
            </div>
        </aside>
    </div>
</x-layouts.app>
