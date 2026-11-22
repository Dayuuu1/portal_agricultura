<x-layouts.app title="Ingresar a la Mesa de Partes Virtual" robots="noindex, nofollow">
    <x-page-header title="Ingresar" subtitle="Acceda a su cuenta de la Mesa de Partes Virtual para presentar documentos y seguir sus trámites."
        :breadcrumbs="['Mesa de partes virtual' => route('mesa-partes.index'), 'Ingresar' => null]" />

    <div class="container-site mt-10 grid grid-cols-1 gap-8 lg:grid-cols-12">
        <div class="lg:col-span-5 lg:col-start-2">
            <x-mesa-partes.flash class="mb-6" />

            <form method="POST" action="{{ route('mesa-partes.login.store') }}" class="card space-y-5 p-6 sm:p-8" novalidate>
                @csrf
                <x-form-field for="email" label="Correo electrónico">
                    <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus
                        class="form-input @error('email') ring-red-500 @enderror" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                </x-form-field>
                <x-form-field for="password" label="Contraseña">
                    <input type="password" id="password" name="password" autocomplete="current-password" required class="form-input">
                </x-form-field>
                <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                    <label class="flex items-center gap-2 text-slate-700">
                        <input type="checkbox" name="remember" value="1" class="rounded text-brand-600 focus:ring-brand-500"> Mantener la sesión iniciada
                    </label>
                    <a href="{{ route('mesa-partes.password.request') }}" class="font-semibold text-brand-700 hover:underline">¿Olvidó su contraseña?</a>
                </div>
                <button type="submit" class="btn-primary w-full">Ingresar</button>
            </form>
        </div>

        <div class="lg:col-span-4 lg:col-start-8">
            <div class="rounded-2xl bg-brand-900 p-6 text-brand-100 sm:p-8">
                <h2 class="font-display text-xl font-bold text-white">¿Primera vez?</h2>
                <p class="mt-2 text-sm">Cree su cuenta con sus datos y su correo. La usará para presentar documentos, recibir el cargo y las notificaciones de sus trámites.</p>
                @if (\App\Support\MesaPartes\MesaPartesConfig::enabled())
                    <a href="{{ route('mesa-partes.register') }}" class="btn-gold mt-6 w-full">Crear una cuenta</a>
                @endif
                <a href="{{ route('mesa-partes.index') }}" class="btn-ghost-light mt-3 w-full">Cómo funciona la mesa de partes</a>
            </div>
        </div>
    </div>
</x-layouts.app>
