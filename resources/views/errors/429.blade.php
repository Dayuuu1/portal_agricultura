<x-layouts.app title="Demasiados intentos" robots="noindex">
    <x-error-page code="429" title="Recibimos muchos envíos seguidos"
        message="Para proteger el portal, limitamos los envíos repetidos. Espere un minuto e inténtelo de nuevo." :search="false" />
</x-layouts.app>
