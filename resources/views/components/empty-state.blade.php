@props(['icon' => 'heroicon-o-information-circle', 'title' => 'No hay resultados', 'message' => null])

<div {{ $attributes->merge(['class' => 'card flex flex-col items-center px-6 py-14 text-center']) }}>
    <span class="mb-4 flex size-14 items-center justify-center rounded-full bg-brand-50 text-brand-600">
        <x-dynamic-component :component="$icon" class="size-7" />
    </span>
    <p class="font-semibold text-slate-900">{{ $title }}</p>
    @if ($message)
        <p class="mt-1 max-w-md text-sm text-slate-500">{{ $message }}</p>
    @endif
    {{ $slot }}
</div>
