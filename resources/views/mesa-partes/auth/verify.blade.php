<x-layouts.app title="Confirme su correo" robots="noindex, nofollow">
    <x-page-header title="Confirme su correo" subtitle="Un paso más para activar su cuenta de la Mesa de Partes Virtual."
        :breadcrumbs="['Mesa de partes virtual' => route('mesa-partes.index'), 'Confirmar correo' => null]" />

    <div class="container-site mt-10 max-w-2xl">
        <x-mesa-partes.flash class="mb-6" />

        <div class="card p-6 text-center sm:p-10">
            <span class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                <x-heroicon-o-envelope-open class="size-8" />
            </span>
            <h2 class="mt-6 font-display text-2xl font-bold text-ink">Revise su bandeja de entrada</h2>
            <p class="mx-auto mt-3 max-w-md text-slate-600">
                Enviamos un enlace a <strong class="text-ink">{{ $citizen->email }}</strong>. Ábralo para confirmar su correo y empezar a presentar documentos.
                Si no lo ve en unos minutos, revise la carpeta de correo no deseado.
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <form method="POST" action="{{ route('mesa-partes.verification.send') }}">
                    @csrf
                    <button type="submit" class="btn-primary"><x-heroicon-o-arrow-path class="size-5" /> Enviar el enlace otra vez</button>
                </form>
                <form method="POST" action="{{ route('mesa-partes.logout') }}">
                    @csrf
                    <button type="submit" class="btn-secondary">Cerrar sesión</button>
                </form>
            </div>
            <p class="mt-6 text-sm text-slate-500">¿Escribió mal su correo? Cierre sesión y cree la cuenta nuevamente, o comuníquese con la entidad.</p>
        </div>
    </div>
</x-layouts.app>
