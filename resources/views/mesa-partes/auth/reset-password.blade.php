<x-layouts.app title="Elegir una nueva contraseña" robots="noindex, nofollow">
    <x-page-header title="Nueva contraseña" subtitle="Elija la contraseña con la que ingresará a su cuenta."
        :breadcrumbs="['Mesa de partes virtual' => route('mesa-partes.index'), 'Nueva contraseña' => null]" />

    <div class="container-site mt-10 max-w-xl">
        <form method="POST" action="{{ route('mesa-partes.password.update') }}" class="card space-y-5 p-6 sm:p-8" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-form-field for="email" label="Correo electrónico">
                <input type="email" id="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required class="form-input @error('email') ring-red-500 @enderror">
            </x-form-field>
            <x-form-field for="password" label="Nueva contraseña" hint="Al menos 10 caracteres, con mayúsculas, minúsculas y números.">
                <input type="password" id="password" name="password" autocomplete="new-password" required class="form-input @error('password') ring-red-500 @enderror">
            </x-form-field>
            <x-form-field for="password_confirmation" label="Repita la contraseña">
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required class="form-input">
            </x-form-field>
            <button type="submit" class="btn-primary w-full">Guardar contraseña</button>
        </form>
    </div>
</x-layouts.app>
