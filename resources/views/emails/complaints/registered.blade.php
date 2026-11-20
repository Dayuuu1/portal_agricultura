<x-mail::message>
# Nuevo reclamo {{ $complaint->code }}

Se registró un reclamo en el Libro de Reclamaciones del portal institucional el **{{ $complaint->created_at->translatedFormat('j \d\e F \d\e Y, H:i') }}**.

<x-mail::panel>
Plazo máximo de respuesta: **{{ $complaint->due_on->translatedFormat('l j \d\e F \d\e Y') }}** ({{ \App\Models\Complaint::RESPONSE_DAYS }} días hábiles, art. 14 del DS N° 007-2020-PCM). Dentro de los 3 días hábiles siguientes al registro, el responsable debe evaluar si la entidad es competente y derivarlo a la oficina que corresponda (art. 16.1).
</x-mail::panel>

@include('emails.complaints.partials.details', ['complaint' => $complaint])

@if (! empty($complaint->attachments))
Se adjuntan {{ count($complaint->attachments) }} archivo(s) enviados por la persona.
@endif

<x-mail::button :url="route('filament.admin.resources.libro-de-reclamaciones.view', $complaint)">
Atender el reclamo en el panel
</x-mail::button>

Este mensaje contiene datos personales protegidos por la Ley N° 29733. No lo reenvíe fuera de la entidad.
</x-mail::message>
