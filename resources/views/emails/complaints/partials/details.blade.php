<x-mail::table>
| Dato | Detalle |
|:-----|:--------|
| Persona | {{ $complaint->full_name }} |
| Documento | {{ $complaint->document_type->getLabel() }} {{ $complaint->document_number }} |
@if ($complaint->is_minor)
| Padre, madre o apoderado | {{ $complaint->guardian_name }} ({{ $complaint->guardian_document_number }}) |
@endif
| Domicilio | {{ $complaint->address }} |
| Teléfono | {{ $complaint->phone }} |
| Correo | {{ $complaint->email ?: '—' }} |
| Fecha del hecho | {{ $complaint->incident_date->format('d/m/Y') }} |
| Sede | {{ $complaint->placeLabel() }} |
| Oficina | {{ $complaint->organizationalUnit?->name ?? 'No precisa' }} |
| Motivo | {{ $complaint->reason->getLabel() }} |
| Notificación por correo | {{ $complaint->notify_by_email ? 'Autorizada' : 'No autorizada' }} |
</x-mail::table>

**Detalle del reclamo**

{!! nl2br(e($complaint->description)) !!}

@if (filled($complaint->request))
**Pedido**

{!! nl2br(e($complaint->request)) !!}
@endif
