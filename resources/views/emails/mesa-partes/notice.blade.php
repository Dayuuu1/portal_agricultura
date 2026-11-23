<x-mail::message>
# {{ $notification->title }}

Estimado(a) {{ \App\Support\MarkdownText::escape($notification->citizen->fullName()) }}:

{!! nl2br(e(\App\Support\MarkdownText::escape($notification->body))) !!}

<x-mail::button :url="route('mesa-partes.notifications.show', $notification)">
Ver en mi cuenta
</x-mail::button>

Este mensaje también quedó en la bandeja de notificaciones de su cuenta de la Mesa de Partes Virtual, con la fecha y hora de depósito.
</x-mail::message>
