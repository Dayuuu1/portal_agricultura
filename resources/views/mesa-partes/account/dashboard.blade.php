<x-mesa-partes.account-layout title="Mis trámites" subtitle="Documentos que presentó en la Mesa de Partes Virtual y su estado.">
    @foreach ($pendingObservations as $observed)
        <div class="flex flex-col gap-4 rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-300 sm:flex-row sm:items-center" role="alert">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-400 text-brand-950"><x-heroicon-o-exclamation-triangle class="size-6" /></span>
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-amber-950">El documento {{ $observed->code }} tiene una observación</p>
                <p class="text-sm text-amber-900">
                    @if ($observed->canBeCured())
                        Subsánela hasta el <strong>{{ $observed->cure_deadline->translatedFormat('l j \d\e F') }}</strong>; si no, el documento se tendrá por no presentado.
                    @else
                        El plazo para subsanarla venció el {{ $observed->cure_deadline->translatedFormat('j \d\e F') }}.
                    @endif
                </p>
            </div>
            <a href="{{ route('mesa-partes.submissions.show', $observed) }}" class="btn-primary shrink-0">{{ $observed->canBeCured() ? 'Subsanar ahora' : 'Ver detalle' }}</a>
        </div>
    @endforeach

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
        @foreach ([
            ['En trámite', $counts['open'], 'heroicon-o-clock', null],
            ['Observados', $counts['observed'], 'heroicon-o-exclamation-triangle', null],
            ['Atendidos', $counts['answered'], 'heroicon-o-check-circle', null],
            ['Notificaciones sin leer', $unread, 'heroicon-o-bell', route('mesa-partes.notifications.index')],
        ] as [$label, $value, $icon, $url])
            <div class="card relative p-5">
                <x-dynamic-component :component="$icon" class="size-6 text-brand-600" />
                <p class="mt-3 font-display text-3xl font-extrabold text-ink">{{ $value }}</p>
                <p class="text-sm text-slate-600">
                    @if ($url)
                        <a href="{{ $url }}" class="after:absolute after:inset-0 hover:text-brand-700">{{ $label }}</a>
                    @else
                        {{ $label }}
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    <section class="card overflow-hidden" aria-labelledby="mis-documentos">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 sm:px-6">
            <h2 id="mis-documentos" class="font-display text-lg font-bold text-ink">Documentos presentados</h2>
            @if ($enabled)
                <a href="{{ route('mesa-partes.submissions.create') }}" class="btn-primary"><x-heroicon-o-document-plus class="size-5" /> Presentar documento</a>
            @endif
        </div>

        @if ($submissions->isEmpty())
            <div class="flex flex-col items-center gap-3 px-6 py-14 text-center">
                <span class="flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700"><x-heroicon-o-inbox class="size-7" /></span>
                <p class="font-display text-lg font-bold text-ink">Aún no presentó documentos</p>
                <p class="max-w-sm text-sm text-slate-600">Cuando presente su primer documento, aquí verá su número de expediente, su estado y los plazos.</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($submissions as $submission)
                    <li class="group relative grid grid-cols-1 gap-3 px-5 py-4 transition-colors hover:bg-brand-50/50 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-6">
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <a href="{{ route('mesa-partes.submissions.show', $submission) }}" class="font-display font-bold text-ink after:absolute after:inset-0 group-hover:text-brand-700">{{ $submission->code }}</a>
                                <span class="text-sm text-slate-500">{{ $submission->procedureType->name }}</span>
                            </p>
                            <p class="mt-0.5 truncate text-sm text-slate-700">{{ $submission->subject }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                Presentado el {{ $submission->filed_on->format('d/m/Y') }}
                                @if ($deadline = $submission->currentDeadline())
                                    · {{ $submission->status === \App\Enums\SubmissionStatus::Observado ? 'Subsanar hasta' : 'Plazo de atención' }} {{ $deadline->format('d/m/Y') }}
                                @endif
                            </p>
                        </div>
                        <x-mesa-partes.status-badge :status="$submission->status" size="sm" />
                    </li>
                @endforeach
            </ul>
            @if ($submissions->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $submissions->links() }}</div>
            @endif
        @endif
    </section>
</x-mesa-partes.account-layout>
