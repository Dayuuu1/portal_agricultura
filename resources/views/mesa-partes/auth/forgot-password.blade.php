<x-layouts.app title="Recuperar contraseña" robots="noindex, nofollow">
    <x-page-header title="Recuperar contraseña" subtitle="Le enviaremos un enlace para que elija una nueva contraseña."
        :breadcrumbs="['Mesa de partes virtual' => route('mesa-partes.index'), 'Recuperar contraseña' => null]" />

    <div class="container-site mt-10 max-w-xl">
        <x-mesa-partes.flash class="mb-6" />

        <form method="POST" action="{{ route('mesa-partes.password.email') }}" class="card space-y-5 p-6 sm:p-8" novalidate>
            @csrf
            <x-form-field for="email" label="Correo electrónico de su cuenta">
                <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="form-input @error('email') ring-red-500 @enderror">
            </x-form-field>
            <button type="submit" class="btn-primary w-full">Enviar enlace</button>
            <p class="text-center text-sm"><a href="{{ route('mesa-partes.login') }}" class="font-semibold text-brand-700 hover:underline">Volver a ingresar</a></p>
        </form>
    </div>
</x-layouts.app>
