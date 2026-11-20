<x-layouts.app title="La sesión expiró" robots="noindex">
    <x-error-page code="419" title="La página estuvo abierta mucho tiempo"
        message="Por seguridad, el formulario dejó de ser válido. Vuelva a la página anterior, actualícela y envíe sus datos otra vez." :search="false">
        <a href="{{ url()->previous() }}" class="btn-primary"><x-heroicon-m-arrow-path class="size-4" /> Volver al formulario</a>
    </x-error-page>
</x-layouts.app>
