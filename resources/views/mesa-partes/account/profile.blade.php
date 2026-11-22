@php
    $inputClass = fn (string $field, string $bag = 'default'): string => 'form-input'.($errors->getBag($bag)->has($field) ? ' ring-red-500' : '');
@endphp

<x-mesa-partes.account-layout title="Mi perfil" subtitle="Sus datos de contacto, las notificaciones electrónicas y su contraseña."
    :breadcrumbs="['Mi cuenta' => route('mesa-partes.account'), 'Mi perfil' => null]">

    <section class="card p-6 sm:p-8" aria-labelledby="identificacion">
        <h2 id="identificacion" class="font-display text-lg font-bold text-ink">Identificación</h2>
        <p class="mt-1 text-sm text-slate-600">Estos datos identifican a quien presenta los documentos. Para corregirlos, comuníquese con la entidad.</p>
        <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">{{ $citizen->person_type->getLabel() }}</dt><dd class="font-semibold text-ink">{{ $citizen->fullName() }}</dd></div>
            <div><dt class="text-slate-500">Documento</dt><dd class="font-semibold text-ink">{{ $citizen->documentLabel() }}</dd></div>
            @if ($citizen->representative_name)
                <div><dt class="text-slate-500">Representante legal</dt><dd class="font-semibold text-ink">{{ $citizen->representative_name }} ({{ $citizen->representative_document_number }})</dd></div>
            @endif
            <div><dt class="text-slate-500">Correo electrónico</dt><dd class="font-semibold text-ink">{{ $citizen->email }}</dd></div>
        </dl>
    </section>

    <form method="POST" action="{{ route('mesa-partes.profile.update') }}" class="card space-y-6 p-6 sm:p-8" novalidate>
        @csrf
        @method('PUT')
        <div>
            <h2 class="font-display text-lg font-bold text-ink">Contacto y domicilio</h2>
            <p class="mt-1 text-sm text-slate-600">El domicilio se usa para las notificaciones que no sean electrónicas.</p>
        </div>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-form-field for="phone" label="Teléfono celular">
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $citizen->phone) }}" required maxlength="20" autocomplete="tel" class="{{ $inputClass('phone') }}">
            </x-form-field>
            <x-form-field for="department" label="Departamento">
                <select id="department" name="department" required class="{{ $inputClass('department') }}">
                    @foreach ($departments as $department)
                        <option value="{{ $department }}" @selected(old('department', $citizen->department) === $department)>{{ $department }}</option>
                    @endforeach
                </select>
            </x-form-field>
            <x-form-field for="province" label="Provincia">
                <input type="text" id="province" name="province" value="{{ old('province', $citizen->province) }}" required maxlength="80" class="{{ $inputClass('province') }}">
            </x-form-field>
        </div>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-form-field for="district" label="Distrito">
                <input type="text" id="district" name="district" value="{{ old('district', $citizen->district) }}" required maxlength="80" class="{{ $inputClass('district') }}">
            </x-form-field>
            <x-form-field for="address" label="Dirección" class="sm:col-span-2">
                <input type="text" id="address" name="address" value="{{ old('address', $citizen->address) }}" required maxlength="255" autocomplete="street-address" class="{{ $inputClass('address') }}">
            </x-form-field>
        </div>

        <label class="flex items-start gap-3 rounded-xl bg-brand-50/60 p-4 text-sm text-slate-700 ring-1 ring-brand-200">
            <input type="checkbox" name="e_notifications" value="1" @checked(old('e_notifications', $citizen->acceptsElectronicNotifications())) class="mt-0.5 rounded text-brand-600 focus:ring-brand-500">
            <span>
                <span class="block font-semibold text-ink">Autorizo que me notifiquen por medios electrónicos</span>
                Las observaciones y respuestas se dejan en mi bandeja y se envían a mi correo (TUO de la Ley N° 27444, art. 20.4).
                @if ($citizen->acceptsElectronicNotifications())
                    <span class="mt-1 block text-xs text-slate-500">Autorizado desde el {{ $citizen->e_notifications_authorized_at->translatedFormat('j \d\e F \d\e Y') }}.</span>
                @endif
            </span>
        </label>

        <div class="flex justify-end"><button type="submit" class="btn-primary">Guardar cambios</button></div>
    </form>

    <form method="POST" action="{{ route('mesa-partes.profile.password') }}" class="card space-y-6 p-6 sm:p-8" novalidate>
        @csrf
        @method('PUT')
        <h2 class="font-display text-lg font-bold text-ink">Cambiar contraseña</h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div>
                <label for="current_password" class="form-label">Contraseña actual</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password" class="{{ $inputClass('current_password', 'password') }}">
                @error('current_password', 'password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="new_password" class="form-label">Nueva contraseña</label>
                <input type="password" id="new_password" name="password" autocomplete="new-password" class="{{ $inputClass('password', 'password') }}">
                @error('password', 'password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="new_password_confirmation" class="form-label">Repita la nueva contraseña</label>
                <input type="password" id="new_password_confirmation" name="password_confirmation" autocomplete="new-password" class="form-input">
            </div>
        </div>
        <div class="flex justify-end"><button type="submit" class="btn-secondary">Actualizar contraseña</button></div>
    </form>

    @if ($email = $settings['email'] ?? null)
        <p class="text-sm text-slate-600">
            Para ejercer sus derechos de acceso, rectificación, cancelación u oposición sobre sus datos personales (Ley N° 29733), escriba a
            <a href="mailto:{{ $email }}" class="font-semibold text-brand-700 hover:underline">{{ $email }}</a>.
        </p>
    @endif
</x-mesa-partes.account-layout>
