<x-mail::table>
| Dato | Detalle |
|:-----|:--------|
| Persona | {{ \App\Support\MarkdownText::escape($complaint->full_name) }} |
| Documento | {{ $complaint->document_type->getLabel() }} {{ \App\Support\MarkdownText::escape($complaint->document_number) }} |
@if ($complaint->is_minor)
| Padre, madre o apoderado | {{ \App\Support\MarkdownText::escape($complaint->guardian_name) }} ({{ \App\Support\MarkdownText::escape($complaint->guardian_document_number) }}) |
@endif
| Domicilio | {{ \App\Support\MarkdownText::escape($complaint->address) }} |
| Teléfono | {{ \App\Support\MarkdownText::escape($complaint->phone) }} |
| Correo | {{ \App\Support\MarkdownText::escape($complaint->email) ?: '—' }} |
| Fecha del hecho | {{ $complaint->incident_date->format('d/m/Y') }} |
| Sede | {{ $complaint->placeLabel() }} |
| Oficina | {{ $complaint->organizationalUnit?->name ?? 'No precisa' }} |
| Motivo | {{ $complaint->reason->getLabel() }} |
| Notificación por correo | {{ $complaint->notify_by_email ? 'Autorizada' : 'No autorizada' }} |
</x-mail::table>

**Detalle del reclamo**

{!! nl2br(e(\App\Support\MarkdownText::escape($complaint->description))) !!}

@if (filled($complaint->request))
**Pedido**

{!! nl2br(e(\App\Support\MarkdownText::escape($complaint->request))) !!}
@endif
