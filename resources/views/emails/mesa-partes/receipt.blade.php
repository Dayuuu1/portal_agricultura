@php($siteName = \App\Models\Setting::valueOf('site_name', config('app.name')))
<x-mail::message>
# Recibimos su documento

Estimado(a) {{ $submission->citizen->fullName() }}:

Su documento ingresó por la Mesa de Partes Virtual de la {{ $siteName }}. Este es su cargo de recepción.

<x-mail::panel>
**N° de expediente: {{ $submission->code }}**<br>
Enviado el {{ $submission->submitted_at->translatedFormat('j \d\e F \d\e Y, H:i') }}<br>
Fecha de presentación: **{{ $submission->filed_on->translatedFormat('j \d\e F \d\e Y') }}**
@if (! $submission->filed_on->isSameDay($submission->submitted_at))
<br><small>Se envió en día u hora no hábil, por lo que se considera presentado el día hábil siguiente (Ley N° 31170).</small>
@endif
</x-mail::panel>

**Trámite:** {{ $submission->procedureType->name }}<br>
**Asunto:** {{ $submission->subject }}<br>
**Folios:** {{ $submission->folios }}
@if ($submission->due_on)
<br>**Plazo de atención:** hasta el {{ $submission->due_on->translatedFormat('j \d\e F \d\e Y') }} ({{ $submission->procedureType->attentionDays() }} días hábiles)
@endif

<x-mail::button :url="route('mesa-partes.submissions.show', $submission)">
Ver mi trámite
</x-mail::button>

La mesa de partes revisará su documento. Si falta algún dato, le dejaremos una observación en su cuenta y tendrá un plazo para subsanarla. Conserve el número de expediente para cualquier consulta.
</x-mail::message>
