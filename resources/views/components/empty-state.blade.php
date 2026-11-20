@props(['icon' => 'heroicon-o-information-circle', 'title' => 'No hay resultados', 'message' => null])

<div {{ $attributes->merge(['class' => 'card relative isolate flex flex-col items-center overflow-hidden px-6 py-14 text-center']) }}>
    <x-site.contours class="absolute inset-0 -z-10 size-full text-brand-200/60 mask-radial-from-10% mask-radial-to-70%" />
    <span class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 ring-1 ring-brand-100">
        <x-dynamic-component :component="$icon" class="size-7" />
    </span>
    <p class="font-display text-lg font-bold text-ink">{{ $title }}</p>
    @if ($message)
        <p class="mt-1 max-w-md text-sm text-slate-500">{{ $message }}</p>
    @endif
    {{ $slot }}
</div>
