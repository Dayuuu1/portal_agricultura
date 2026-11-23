<x-mail::message>
# {{ $isCure ? 'Subsanación recibida' : 'Nuevo documento recibido' }}

<x-mail::panel>
**{{ $submission->code }}** · {{ $submission->procedureType->name }}<br>
Asunto: {{ \App\Support\MarkdownText::escape($submission->subject) }}<br>
Administrado: {{ \App\Support\MarkdownText::escape($submission->citizen->fullName()) }} ({{ \App\Support\MarkdownText::escape($submission->citizen->documentLabel()) }})<br>
Fecha de presentación: {{ $submission->filed_on->format('d/m/Y') }}
</x-mail::panel>

{{ $isCure ? 'El administrado completó la observación. Revísela para admitir y derivar el documento.' : 'Revise el documento para admitirlo y derivarlo, u observarlo si falta algún requisito.' }}

<x-mail::button :url="\App\Filament\Resources\Submissions\SubmissionResource::getUrl('view', ['record' => $submission])">
Revisar en el panel
</x-mail::button>
</x-mail::message>
