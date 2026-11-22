<x-mesa-partes.account-layout title="Notificaciones" subtitle="Mensajes de la entidad sobre sus trámites, con la fecha y hora en que se dejaron en su cuenta."
    :breadcrumbs="['Mi cuenta' => route('mesa-partes.account'), 'Notificaciones' => null]">

    <section class="card overflow-hidden">
        @if ($notifications->isEmpty())
            <div class="flex flex-col items-center gap-3 px-6 py-14 text-center">
                <span class="flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700"><x-heroicon-o-bell class="size-7" /></span>
                <p class="font-display text-lg font-bold text-ink">No tiene notificaciones</p>
                <p class="max-w-sm text-sm text-slate-600">Aquí recibirá las observaciones, derivaciones y respuestas de sus trámites.</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($notifications as $notification)
                    <li class="group relative flex items-start gap-4 px-5 py-4 transition-colors hover:bg-brand-50/50 sm:px-6">
                        <span @class(['mt-1.5 size-2.5 shrink-0 rounded-full', 'bg-gold-500' => ! $notification->read_at, 'bg-transparent' => $notification->read_at]) aria-hidden="true"></span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('mesa-partes.notifications.show', $notification) }}" @class(['block text-ink after:absolute after:inset-0 group-hover:text-brand-700', 'font-bold' => ! $notification->read_at, 'font-semibold' => $notification->read_at])>
                                {{ $notification->title }}
                                @unless ($notification->read_at)<span class="sr-only">(sin leer)</span>@endunless
                            </a>
                            <p class="mt-0.5 line-clamp-2 text-sm text-slate-600">{{ $notification->body }}</p>
                        </div>
                        <time datetime="{{ $notification->deposited_at->toIso8601String() }}" class="shrink-0 text-xs text-slate-500">{{ $notification->deposited_at->format('d/m/Y H:i') }}</time>
                    </li>
                @endforeach
            </ul>
            @if ($notifications->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $notifications->links() }}</div>
            @endif
        @endif
    </section>
</x-mesa-partes.account-layout>
