{{--
    Copy of the portal's announcement window (components/site/announcements.blade.php) at its real width, over the
    darkened page, fed with the form state (AnnouncementResource::previewData). Portal colours go inline: the panel
    theme does not have them.
--}}
@php
    use App\Enums\AnnouncementLayout;
    use Filament\Support\Icons\Heroicon;
@endphp

@php
    $gold = $kind->isGold();
    $isPoster = $image && $layout === AnnouncementLayout::Poster;
    $title = filled($title) ? $title : 'Título del anuncio';
    $badgeStyle = $gold
        ? 'background: #faedc8; color: #082417; box-shadow: inset 0 0 0 1px rgb(235 180 58 / 0.6);'
        : 'background: #effaf2; color: #154f31; box-shadow: inset 0 0 0 1px #b4e6c3;';
@endphp

<div class="flex justify-center rounded-xl px-3 py-6" style="background-color: #3d5246; background-image: linear-gradient(rgb(8 36 23 / 0.72), rgb(8 36 23 / 0.72)), radial-gradient(circle at 1px 1px, rgb(255 255 255 / 0.25) 1px, transparent 0); background-size: auto, 18px 18px;">
    <div class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white text-left shadow-2xl" style="color: #334155;">
        <span class="absolute top-3 right-3 z-10 flex h-9 w-9 items-center justify-center rounded-full shadow-md ring-1 ring-gray-950/10" style="background: rgb(255 255 255 / 0.9); color: #334155;">
            <x-filament::icon :icon="Heroicon::XMark" class="h-5 w-5" />
        </span>

        @if ($isPoster)
            <div class="flex justify-center" style="background: #082417;">
                <img src="{{ $image }}" alt="" class="w-auto max-w-full object-contain" style="max-height: 26rem;">
            </div>
            <div class="flex items-center justify-between gap-4 px-5 py-4">
                <div class="min-w-0">
                    <p class="inline-flex items-center gap-1.5 text-xs font-semibold" style="color: {{ $gold ? '#bd7418' : '#17633a' }};">
                        <x-filament::icon :icon="$kind->getIcon()" class="h-4 w-4" />
                        {{ $kind->getLabel() }}
                    </p>
                    <p class="mt-1 text-base leading-snug font-bold" style="color: #16261d;">{{ $title }}</p>
                </div>
                @if ($buttonLabel)
                    <span class="shrink-0 rounded-xl px-4 py-2.5 text-sm font-semibold text-white" style="background: #1c7c45;">{{ $buttonLabel }} →</span>
                @endif
            </div>
        @else
            @if ($image)
                <img src="{{ $image }}" alt="" class="w-full object-cover" style="aspect-ratio: 16 / 9; background: #154f31;">
            @else
                <div class="px-6 pt-6 pb-7 text-white" style="background: #0e3b25;">
                    <div class="flex items-center gap-3 pr-10">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl" style="{{ $gold ? 'background: #ebb43a; color: #082417;' : 'background: rgb(255 255 255 / 0.1); color: #f1c95f; box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.2);' }}">
                            <x-filament::icon :icon="$kind->getIcon()" class="h-6 w-6" />
                        </span>
                        <p class="text-sm leading-tight">
                            <span class="block font-semibold" style="color: #f1c95f;">{{ $kind->getLabel() }}</span>
                            <span style="color: rgb(216 243 223 / 0.8);">{{ $origin }}</span>
                        </p>
                    </div>
                    <p class="mt-5 text-2xl leading-tight font-extrabold">{{ $title }}</p>
                </div>
            @endif

            <div class="px-6 pt-5 pb-6">
                @if ($image)
                    <p class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" style="{{ $badgeStyle }}">
                        <x-filament::icon :icon="$kind->getIcon()" class="h-4 w-4" />
                        {{ $kind->getLabel() }}
                    </p>
                    <p class="mt-3 text-2xl leading-tight font-extrabold" style="color: #16261d;">{{ $title }}</p>
                @endif

                @if (filled($text))
                    <p class="{{ $image ? 'mt-3' : '' }} text-sm leading-relaxed whitespace-pre-line" style="color: #475569;">{{ $text }}</p>
                @endif

                <div class="mt-6 flex flex-wrap items-center gap-2">
                    @if ($buttonLabel)
                        <span class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white" style="background: #1c7c45;">{{ $buttonLabel }} →</span>
                    @endif
                    <span class="rounded-xl px-4 py-2.5 text-sm font-semibold" style="color: #475569;">{{ $buttonLabel ? 'Ahora no' : 'Entendido' }}</span>
                </div>
            </div>
        @endif
    </div>
</div>

@if ($layout === AnnouncementLayout::Poster && ! $image)
    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">El diseño «Afiche» necesita una imagen. Mientras no la suba, se muestra como comunicado.</p>
@endif
