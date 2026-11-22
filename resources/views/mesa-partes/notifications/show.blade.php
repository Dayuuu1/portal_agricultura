<x-mesa-partes.account-layout :title="$notification->title"
    :breadcrumbs="['Mi cuenta' => route('mesa-partes.account'), 'Notificaciones' => route('mesa-partes.notifications.index'), 'Mensaje' => null]">

    <article class="card overflow-hidden">
        <dl class="grid grid-cols-1 gap-px border-b border-slate-100 bg-slate-100 text-sm sm:grid-cols-3">
            <div class="bg-white p-4"><dt class="text-slate-500">Depositada en su cuenta</dt><dd class="font-semibold text-ink">{{ $notification->deposited_at->format('d/m/Y H:i:s') }}</dd></div>
            <div class="bg-white p-4"><dt class="text-slate-500">Leída por primera vez</dt><dd class="font-semibold text-ink">{{ $notification->read_at?->format('d/m/Y H:i:s') ?? '—' }}</dd></div>
            <div class="bg-white p-4"><dt class="text-slate-500">Enviada a su correo</dt><dd class="font-semibold text-ink">{{ $notification->emailed_at?->format('d/m/Y H:i') ?? 'No' }}</dd></div>
        </dl>
        <div class="p-6 sm:p-8">
            <p class="whitespace-pre-line text-slate-800">{{ $notification->body }}</p>
            @if ($notification->submission)
                <a href="{{ route('mesa-partes.submissions.show', $notification->submission) }}" class="btn-primary mt-6">Ver el expediente {{ $notification->submission->code }}</a>
            @endif
        </div>
    </article>
</x-mesa-partes.account-layout>
