{{-- Frame of the citizen's account: header, side navigation and the page content. --}}
@props(['title', 'subtitle' => null, 'breadcrumbs' => []])

@php
    $citizen = auth('citizen')->user();
    $unread = $citizen?->inbox()->unread()->count() ?? 0;
    $links = [
        ['label' => 'Mis trámites', 'route' => 'mesa-partes.account', 'active' => ['mesa-partes.account', 'mesa-partes.submissions.show', 'mesa-partes.submissions.receipt'], 'icon' => 'heroicon-o-folder-open'],
        ['label' => 'Presentar documento', 'short' => 'Presentar', 'route' => 'mesa-partes.submissions.create', 'active' => ['mesa-partes.submissions.create'], 'icon' => 'heroicon-o-document-plus'],
        ['label' => 'Notificaciones', 'route' => 'mesa-partes.notifications.index', 'active' => ['mesa-partes.notifications.*'], 'icon' => 'heroicon-o-bell', 'badge' => $unread],
        ['label' => 'Mi perfil', 'route' => 'mesa-partes.profile', 'active' => ['mesa-partes.profile'], 'icon' => 'heroicon-o-user-circle'],
    ];
@endphp

<x-layouts.app :title="$title" robots="noindex, nofollow">
    <x-page-header :title="$title" :subtitle="$subtitle" :breadcrumbs="['Mesa de partes virtual' => route('mesa-partes.index'), ...$breadcrumbs]" />

    <div class="container-site mt-10 grid grid-cols-1 gap-8 lg:grid-cols-12">
        <aside class="lg:col-span-3" aria-label="Mi cuenta">
            <div class="card p-3 lg:sticky lg:top-24 lg:p-4">
                @if ($citizen)
                    <div class="flex items-center gap-3 border-b border-slate-100 px-2 pb-3 lg:pb-4">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-600 font-display text-sm font-bold text-white" aria-hidden="true">
                            {{ str($citizen->fullName())->explode(' ')->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') }}
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-ink">{{ $citizen->fullName() }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $citizen->documentLabel() }}</span>
                        </span>
                        <form method="POST" action="{{ route('mesa-partes.logout') }}" class="ml-auto lg:hidden">
                            @csrf
                            <button type="submit" class="flex size-10 items-center justify-center rounded-xl text-slate-500 transition-colors hover:bg-slate-100 hover:text-ink" title="Cerrar sesión">
                                <x-heroicon-o-arrow-right-start-on-rectangle class="size-5" />
                                <span class="sr-only">Cerrar sesión</span>
                            </button>
                        </form>
                    </div>
                @endif
                <nav class="mt-3">
                    <ul class="grid grid-cols-2 gap-1 lg:block lg:space-y-0.5">
                        @foreach ($links as $link)
                            @php($isActive = request()->routeIs(...$link['active']))
                            <li>
                                <a href="{{ route($link['route']) }}" @if ($isActive) aria-current="page" @endif
                                    @class([
                                        'flex items-center gap-2 rounded-xl px-2.5 py-2.5 text-[13px] font-semibold transition-colors lg:gap-3 lg:px-3 lg:text-sm',
                                        'bg-brand-600 text-white shadow-sm shadow-brand-900/20' => $isActive,
                                        'text-slate-700 hover:bg-brand-50 hover:text-brand-800' => ! $isActive,
                                    ])>
                                    <span class="relative shrink-0">
                                        <x-dynamic-component :component="$link['icon']" class="size-5" />
                                        @if (($link['badge'] ?? 0) > 0)
                                            <span class="absolute -top-1.5 -right-1.5 size-2.5 rounded-full bg-gold-400 ring-2 ring-white lg:hidden"><span class="sr-only">{{ $link['badge'] }} sin leer</span></span>
                                        @endif
                                    </span>
                                    <span class="min-w-0 flex-1 truncate">
                                        @isset($link['short'])
                                            <span class="lg:hidden">{{ $link['short'] }}</span><span class="hidden lg:inline">{{ $link['label'] }}</span>
                                        @else
                                            {{ $link['label'] }}
                                        @endisset
                                    </span>
                                    @if (($link['badge'] ?? 0) > 0)
                                        <span @class(['hidden rounded-full px-2 py-0.5 text-xs lg:inline-block', 'bg-white text-brand-800' => $isActive, 'bg-gold-400 text-brand-950' => ! $isActive])>
                                            {{ $link['badge'] }}<span class="sr-only"> sin leer</span>
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
                <form method="POST" action="{{ route('mesa-partes.logout') }}" class="mt-3 hidden border-t border-slate-100 pt-3 lg:block">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-100 hover:text-ink">
                        <x-heroicon-o-arrow-right-start-on-rectangle class="size-5 shrink-0" />
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <div class="min-w-0 space-y-6 lg:col-span-9">
            <x-mesa-partes.flash />
            {{ $slot }}
        </div>
    </div>
</x-layouts.app>
