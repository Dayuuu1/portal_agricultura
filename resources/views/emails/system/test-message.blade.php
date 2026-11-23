<x-mail::message>
# El correo del portal funciona

Este mensaje salió por el servidor **{{ $host }}** el {{ now()->translatedFormat('j \d\e F \d\e Y, H:i') }}, desde «Correo y acceso» del panel de administración.

Si lo recibió en la bandeja de entrada, puede activar el servidor y guardar la configuración. Si llegó a la carpeta de spam, pida al área de TI que revise los registros SPF y DKIM del dominio del remitente.

{{ \App\Models\Setting::valueOf('site_name', config('app.name')) }}
</x-mail::message>
