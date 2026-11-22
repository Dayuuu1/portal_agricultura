{{-- Confirmation left by the previous action (session «status»). --}}
@if (session('status'))
    <div {{ $attributes->class('flex items-start gap-3 rounded-xl bg-brand-50 p-4 text-brand-950 ring-1 ring-brand-200') }} role="status">
        <x-heroicon-o-check-circle class="size-6 shrink-0 text-brand-600" />
        <p class="font-medium">{{ session('status') }}</p>
    </div>
@endif
