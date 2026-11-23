<x-mail::message>
# Respuesta a su reclamo {{ $complaint->code }}

Estimado(a) {{ \App\Support\MarkdownText::escape($complaint->full_name) }}:

En atención a su reclamo registrado el {{ $complaint->created_at->translatedFormat('j \d\e F \d\e Y') }}, le comunicamos que fue **{{ $complaint->status === \App\Enums\ComplaintStatus::Aceptado ? 'aceptado' : 'denegado' }}**.

<x-mail::panel>
{!! nl2br(e(\App\Support\MarkdownText::escape($complaint->response))) !!}
</x-mail::panel>

Con esta respuesta concluye el proceso de gestión de su reclamo (DS N° 007-2020-PCM, art. 17). Si no está conforme, puede acudir a las demás vías que la ley le reconoce.

Atentamente,<br>
{{ \App\Models\Setting::valueOf('site_name', config('app.name')) }}
</x-mail::message>
