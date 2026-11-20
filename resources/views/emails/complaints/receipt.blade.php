<x-mail::message>
# Recibimos su reclamo

Estimado(a) {{ $complaint->full_name }}:

Su reclamo quedó registrado en el Libro de Reclamaciones de la {{ \App\Models\Setting::valueOf('site_name', config('app.name')) }}. Esta es la copia de su registro.

<x-mail::panel>
**N° de reclamo: {{ $complaint->code }}**<br>
Fecha de registro: {{ $complaint->created_at->translatedFormat('j \d\e F \d\e Y, H:i') }}<br>
Responderemos a más tardar el **{{ $complaint->due_on->translatedFormat('j \d\e F \d\e Y') }}** ({{ \App\Models\Complaint::RESPONSE_DAYS }} días hábiles).
</x-mail::panel>

@include('emails.complaints.partials.details', ['complaint' => $complaint])

@if ($complaint->canBeNotifiedByEmail())
Como lo autorizó, le enviaremos la respuesta a este correo electrónico.
@else
La respuesta se le notificará en el domicilio que indicó.
@endif

El registro y la atención de su reclamo son gratuitos. Conserve el número de reclamo para cualquier consulta.
</x-mail::message>
