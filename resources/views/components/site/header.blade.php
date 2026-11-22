@php
    $institutionalLinks = [
        ['label' => 'Nosotros', 'url' => route('institutional.about'), 'hint' => 'Quiénes somos, misión y visión', 'icon' => 'heroicon-o-building-library'],
        ['label' => 'Organigrama', 'url' => route('institutional.organization-chart'), 'hint' => 'Estructura orgánica', 'icon' => 'heroicon-o-squares-2x2'],
        ['label' => 'Directorio', 'url' => route('institutional.directory'), 'hint' => 'Autoridades y responsables', 'icon' => 'heroicon-o-identification'],
        ['label' => 'Agencias agrarias', 'url' => route('agencies.index'), 'hint' => 'Atención en provincias', 'icon' => 'heroicon-o-map-pin'],
        // Pages marked «Mostrar en el menú Institucional» in the panel (the code of ethics, for instance).
        ...array_map(fn (array $page): array => ['label' => $page['name'], 'url' => url($page['url']), 'hint' => $page['hint'], 'icon' => $page['icon']], $menu['pages'] ?? []),
    ];

    $agrarianLinks = [
        ['label' => 'Resumen de información agraria', 'url' => route('agrarian-information')],
        ...array_map(fn (array $type): array => ['label' => $type['name'], 'url' => url($type['url'])], $menu['agrarianTypes']),
    ];

    $navigation = [
        ['key' => 'inicio', 'label' => 'Inicio', 'url' => route('home'), 'active' => ['home']],
        ['key' => 'institucional', 'label' => 'Institucional', 'active' => ['institutional.*', 'agencies.*', 'pages.show'], 'mega' => true],
        ['key' => 'informacion', 'label' => 'Información agraria', 'url' => route('agrarian-information'), 'active' => ['agrarian-information'], 'children' => $agrarianLinks],
        ['key' => 'noticias', 'label' => 'Noticias', 'url' => route('posts.index'), 'active' => ['posts.*']],
        ['key' => 'documentos', 'label' => 'Documentos', 'url' => route('documents.index'), 'active' => ['documents.*'], 'children' => [
            ['label' => 'Normas y resoluciones', 'url' => route('documents.index', ['seccion' => 'normativa'])],
            ['label' => 'Gestión institucional (TUPA, ROF, POI…)', 'url' => route('documents.index', ['seccion' => 'gestion'])],
            ['label' => 'Información agraria', 'url' => route('documents.index', ['seccion' => 'informacion-agraria'])],
            ['label' => 'Todos los documentos', 'url' => route('documents.index')],
        ]],
        ['key' => 'convocatorias', 'label' => 'Convocatorias', 'url' => route('job-postings.index'), 'active' => ['job-postings.*']],
        ['key' => 'transparencia', 'label' => 'Transparencia', 'url' => route('transparency'), 'active' => ['transparency']],
        ['key' => 'contacto', 'label' => 'Contacto', 'url' => route('contact.create'), 'active' => ['contact.*']],
    ];

    $topLinkClasses = 'relative flex items-center gap-1 px-3 py-3.5 text-sm font-semibold text-white/90 transition-colors duration-200 hover:text-white after:absolute after:inset-x-3 after:bottom-0 after:h-[3px] after:origin-center after:scale-x-0 after:rounded-t-full after:bg-gold-400 after:transition-transform after:duration-300 after:ease-out-quint hover:after:scale-x-100';
@endphp

{{-- La cabecera completa es "sticky" con desplazamiento negativo: al bajar solo queda visible la franja del menú. --}}
<header class="group/header sticky -top-29 z-40 max-lg:-top-9 print:hidden" data-site-header x-data="{ mobileOpen: false }" @keydown.escape.window="mobileOpen = false">
    {{-- Barra superior --}}
    <div class="bg-brand-950 text-xs text-brand-100">
        <div class="container-site flex h-9 items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-2.5">
                <span class="flex h-3 w-[1.1rem] shrink-0 overflow-hidden rounded-xs" aria-hidden="true">
                    <span class="w-1/3 bg-gob"></span><span class="w-1/3 bg-white"></span><span class="w-1/3 bg-gob"></span>
                </span>
                <span class="truncate font-medium">{{ $settings['parent_entity'] ?? 'Gobierno Regional de Madre de Dios' }}</span>
            </div>
            <div class="flex shrink-0 items-center gap-4">
                @if (! empty($settings['phone_main']))
                    <a href="tel:{{ preg_replace('/\s+/', '', $settings['phone_main']) }}" class="hidden items-center gap-1.5 hover:text-white md:flex">
                        <x-heroicon-o-phone class="size-3.5" /> {{ $settings['phone_main'] }}
                    </a>
                @endif
                @if (! empty($settings['email']))
                    <a href="mailto:{{ $settings['email'] }}" class="hidden items-center gap-1.5 hover:text-white lg:flex">
                        <x-heroicon-o-envelope class="size-3.5" /> {{ $settings['email'] }}
                    </a>
                @endif
                @if (\App\Support\MesaPartes\MesaPartesConfig::enabled())
                    <a href="{{ route('mesa-partes.index') }}" class="hidden items-center gap-1.5 hover:text-white sm:flex">
                        <x-heroicon-o-inbox-arrow-down class="size-3.5" /> Mesa de partes
                    </a>
                @endif
                <a href="{{ route('complaints.create') }}" class="hidden items-center gap-1.5 hover:text-white sm:flex">
                    <x-heroicon-o-book-open class="size-3.5" /> Libro de reclamaciones
                </a>
                <span class="flex items-center gap-2.5">
                    @if (! empty($settings['facebook_url']))
                        <a href="{{ $settings['facebook_url'] }}" target="_blank" rel="noopener" class="hover:text-white" aria-label="Facebook">
                            <x-site.social-icon network="facebook" />
                        </a>
                    @endif
                    @if (! empty($settings['youtube_url']))
                        <a href="{{ $settings['youtube_url'] }}" target="_blank" rel="noopener" class="hover:text-white" aria-label="YouTube">
                            <x-site.social-icon network="youtube" />
                        </a>
                    @endif
                </span>
                @if (! empty($settings['sigaweb_url']))
                    <a href="{{ $settings['sigaweb_url'] }}" target="_blank" rel="noopener" class="hidden items-center gap-1.5 font-semibold hover:text-white md:flex" title="Sistema de uso interno del personal">
                        <x-heroicon-o-computer-desktop class="size-3.5" /> SIGAWEB
                    </a>
                @endif
                {{-- Staff of the panel and citizens of the Mesa de Partes have separate sessions. --}}
                @auth('web')
                    <a href="{{ route('filament.admin.pages.dashboard') }}" class="flex items-center gap-1.5 rounded-md bg-gold-400 px-2.5 py-1 font-semibold text-brand-950 hover:bg-gold-300">
                        <x-heroicon-o-squares-2x2 class="size-3.5" />
                        <span class="hidden sm:inline">Panel de administración</span><span class="sm:hidden">Panel</span>
                    </a>
                @elseauth('citizen')
                    <a href="{{ route('mesa-partes.account') }}" class="flex items-center gap-1.5 rounded-md bg-gold-400 px-2.5 py-1 font-semibold text-brand-950 hover:bg-gold-300">
                        <x-heroicon-o-folder-open class="size-3.5" />
                        <span>Mis trámites</span>
                    </a>
                @else
                    <a href="{{ route('filament.admin.auth.login') }}" class="flex items-center gap-1.5 rounded-md bg-white/10 px-2.5 py-1 font-semibold text-white ring-1 ring-white/20 hover:bg-white/20">
                        <x-heroicon-o-user-circle class="size-3.5" />
                        <span class="hidden sm:inline">Acceso institucional</span><span class="sm:hidden">Personal</span>
                    </a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Identidad y buscador --}}
    <div class="border-b border-slate-200/80 bg-white/95 backdrop-blur-md transition-shadow duration-300 max-lg:group-data-[scrolled]/header:shadow-lg max-lg:group-data-[scrolled]/header:shadow-brand-950/10">
        <div class="container-site flex h-20 items-center justify-between gap-6">
            <a href="{{ route('home') }}" class="min-w-0" aria-label="Ir al inicio">
                <x-site.logo />
            </a>

            <form method="GET" action="{{ route('search') }}" role="search" class="hidden w-full max-w-md items-center lg:flex">
                <label for="busqueda-cabecera" class="sr-only">Buscar en el portal</label>
                <div class="relative flex-1">
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-slate-400" />
                    <input type="search" id="busqueda-cabecera" name="q" minlength="3" placeholder="Buscar resoluciones, convocatorias, noticias…"
                        aria-keyshortcuts="/" class="peer form-input rounded-r-none py-2.5 pr-10 pl-10">
                    <kbd class="pointer-events-none absolute top-1/2 right-3 hidden -translate-y-1/2 rounded-md bg-slate-100 px-1.5 py-0.5 font-sans text-xs font-semibold text-slate-500 ring-1 ring-slate-200 peer-placeholder-shown:inline-block peer-focus:hidden" title="Pulse / para buscar">/</kbd>
                </div>
                <button type="submit" class="btn-primary rounded-l-none py-2.5">Buscar</button>
            </form>

            <div class="flex shrink-0 items-center gap-1 lg:hidden">
                <a href="{{ route('search') }}" class="flex size-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 hover:text-brand-700" aria-label="Buscar en el portal">
                    <x-heroicon-o-magnifying-glass class="size-5" />
                </a>
                <button type="button" class="flex size-10 items-center justify-center rounded-lg text-slate-700 hover:bg-slate-100"
                    @click="mobileOpen = true" aria-label="Abrir menú" :aria-expanded="mobileOpen.toString()" aria-controls="menu-movil">
                    <x-heroicon-o-bars-3 class="size-6" />
                </button>
            </div>
        </div>
    </div>

    {{-- Menú principal (escritorio) --}}
    <nav class="hidden bg-brand-800 transition-shadow duration-300 group-data-[scrolled]/header:shadow-xl group-data-[scrolled]/header:shadow-brand-950/25 lg:block" aria-label="Navegación principal">
        <div class="container-site relative">
            <ul class="flex items-stretch">
                @foreach ($navigation as $item)
                    @php($isActive = request()->routeIs(...$item['active']))
                    @php($activeClasses = $isActive ? 'bg-brand-900/60 text-white after:scale-x-100' : '')

                    @if (isset($item['children']) || isset($item['mega']))
                        <li @class(['static' => isset($item['mega']), 'relative' => ! isset($item['mega'])]) data-menu="{{ $item['key'] }}"
                            x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false"
                            @focusout="if (! $el.contains($event.relatedTarget)) open = false">
                            @isset($item['url'])
                                {{-- El nombre lleva a la página de la sección; la flecha abre el submenú (también se abre al pasar el cursor). --}}
                                <div @class(['flex h-full items-stretch', 'bg-brand-900/60' => $isActive])>
                                    <a href="{{ $item['url'] }}" @class([$topLinkClasses, 'pr-1.5', 'text-white after:scale-x-100' => $isActive])>{{ $item['label'] }}</a>
                                    <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-label="Más opciones de {{ $item['label'] }}"
                                        class="flex items-center pr-2.5 pl-0.5 text-white/80 transition-colors hover:text-white">
                                        <x-heroicon-o-chevron-down class="size-4 transition" ::class="open && 'rotate-180'" />
                                    </button>
                                </div>
                            @else
                                <button type="button" @click="open = ! open" :aria-expanded="open.toString()" class="{{ $topLinkClasses }} {{ $activeClasses }}">
                                    {{ $item['label'] }}
                                    <x-heroicon-o-chevron-down class="size-4 transition" ::class="open && 'rotate-180'" />
                                </button>
                            @endisset

                            @isset($item['mega'])
                                {{-- Menú institucional: igual que el antiguo menú «DRAMDD», con todas las oficinas y sus unidades --}}
                                <div x-cloak x-show="open" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="-translate-y-2 opacity-0" x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="-translate-y-1 opacity-0" class="absolute inset-x-4 top-full z-50 pt-2 sm:inset-x-6 lg:inset-x-8">
                                    <div class="card grid max-h-[75vh] gap-8 overflow-y-auto p-6 shadow-2xl shadow-brand-950/20 lg:grid-cols-12">
                                        <div class="lg:col-span-3 lg:border-r lg:border-slate-100 lg:pr-6">
                                            <p class="mb-3 text-sm font-semibold text-slate-500">La institución</p>
                                            <ul class="space-y-1">
                                                @foreach ($institutionalLinks as $link)
                                                    <li>
                                                        <a href="{{ $link['url'] }}" class="group flex items-start gap-3 rounded-xl p-2.5 transition-colors hover:bg-brand-50">
                                                            <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-700 transition-colors group-hover:bg-brand-600 group-hover:text-white">
                                                                <x-dynamic-component :component="$link['icon']" class="size-4" />
                                                            </span>
                                                            <span>
                                                                <span class="block text-sm font-semibold text-slate-900">{{ $link['label'] }}</span>
                                                                <span class="block text-xs text-slate-500">{{ $link['hint'] }}</span>
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        <div class="lg:col-span-9">
                                            <p class="mb-3 text-sm font-semibold text-slate-500">Órganos y unidades</p>
                                            <ul class="grid gap-x-8 gap-y-5 sm:grid-cols-2 xl:grid-cols-3">
                                                @foreach ($menu['offices'] as $office)
                                                    <li>
                                                        <a href="{{ url($office['url']) }}" class="group flex items-start gap-2 text-sm font-semibold text-slate-900 hover:text-brand-700">
                                                            <x-heroicon-o-chevron-right class="mt-0.5 size-4 shrink-0 text-brand-500" />
                                                            {{ $office['name'] }}
                                                        </a>
                                                        @if ($office['children'] !== [])
                                                            <ul class="mt-1.5 ml-6 space-y-1 border-l border-slate-200 pl-3">
                                                                @foreach ($office['children'] as $unit)
                                                                    <li><a href="{{ url($unit['url']) }}" class="block text-xs text-slate-600 hover:text-brand-700 hover:underline">{{ $unit['name'] }}</a></li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </li>
                                                @endforeach
                                                @if ($menu['agencies'] !== [])
                                                    <li>
                                                        <a href="{{ route('agencies.index') }}" class="group flex items-start gap-2 text-sm font-semibold text-slate-900 hover:text-brand-700">
                                                            <x-heroicon-o-chevron-right class="mt-0.5 size-4 shrink-0 text-brand-500" />
                                                            Agencias agrarias
                                                        </a>
                                                        <ul class="mt-1.5 ml-6 space-y-1 border-l border-slate-200 pl-3">
                                                            @foreach ($menu['agencies'] as $agency)
                                                                <li><a href="{{ url($agency['url']) }}" class="block text-xs text-slate-600 hover:text-brand-700 hover:underline">{{ $agency['name'] }}</a></li>
                                                            @endforeach
                                                        </ul>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div x-cloak x-show="open" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="-translate-y-2 opacity-0" x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="-translate-y-1 opacity-0" class="absolute top-full left-0 z-50 w-80 pt-2">
                                    <ul class="card overflow-hidden p-2 shadow-2xl shadow-brand-950/20">
                                        @foreach ($item['children'] as $child)
                                            <li>
                                                <a href="{{ $child['url'] }}" class="group flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-brand-50 hover:text-brand-800">
                                                    {{ $child['label'] }}
                                                    <x-heroicon-m-chevron-right class="size-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-brand-600" />
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endisset
                        </li>
                    @else
                        <li>
                            <a href="{{ $item['url'] }}" @if ($isActive) aria-current="page" @endif class="{{ $topLinkClasses }} {{ $activeClasses }}">
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </nav>

    {{-- Menú móvil --}}
    <div x-cloak x-show="mobileOpen" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menú de navegación">
        <div x-show="mobileOpen" x-transition.opacity class="absolute inset-0 bg-brand-950/60" @click="mobileOpen = false"></div>
        <div id="menu-movil" x-show="mobileOpen" x-transition:enter="transition duration-200" x-transition:enter-start="translate-x-full" x-transition:leave="transition duration-150" x-transition:leave-end="translate-x-full"
            class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col overflow-y-auto bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4">
                <x-site.logo />
                <button type="button" class="flex size-10 items-center justify-center rounded-lg hover:bg-slate-100" @click="mobileOpen = false" aria-label="Cerrar menú">
                    <x-heroicon-o-x-mark class="size-6" />
                </button>
            </div>
            <nav class="flex-1 px-4 py-4" aria-label="Navegación móvil">
                <ul class="space-y-1">
                    @foreach ($navigation as $item)
                        @php($isActive = request()->routeIs(...$item['active']))
                        @if (isset($item['children']) || isset($item['mega']))
                            <li x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
                                <button type="button" @click="open = ! open" :aria-expanded="open.toString()" class="flex w-full items-center justify-between rounded-lg px-3 py-3 text-left font-semibold text-slate-800 hover:bg-slate-50">
                                    {{ $item['label'] }}
                                    <x-heroicon-o-chevron-down class="size-5 transition" ::class="open && 'rotate-180'" />
                                </button>
                                <ul x-show="open" x-collapse class="ml-3 border-l-2 border-brand-100 pl-3">
                                    @if (isset($item['mega']))
                                        @foreach ($institutionalLinks as $link)
                                            <li><a href="{{ $link['url'] }}" class="block rounded-lg px-3 py-2.5 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-800">{{ $link['label'] }}</a></li>
                                        @endforeach
                                        <li class="px-3 pt-3 pb-1 text-sm font-semibold text-slate-500">Órganos y unidades</li>
                                        @foreach ($menu['offices'] as $office)
                                            <li x-data="{ sub: false }">
                                                <div class="flex items-center">
                                                    <a href="{{ url($office['url']) }}" class="flex-1 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-brand-50 hover:text-brand-800">{{ $office['name'] }}</a>
                                                    @if ($office['children'] !== [])
                                                        <button type="button" @click="sub = ! sub" :aria-expanded="sub.toString()" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Ver unidades de {{ $office['name'] }}">
                                                            <x-heroicon-o-chevron-down class="size-4 transition" ::class="sub && 'rotate-180'" />
                                                        </button>
                                                    @endif
                                                </div>
                                                @if ($office['children'] !== [])
                                                    <ul x-show="sub" x-collapse class="mb-1 ml-3 border-l border-slate-200 pl-3">
                                                        @foreach ($office['children'] as $unit)
                                                            <li><a href="{{ url($unit['url']) }}" class="block rounded-lg px-3 py-2 text-xs text-slate-600 hover:bg-brand-50 hover:text-brand-800">{{ $unit['name'] }}</a></li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </li>
                                        @endforeach
                                    @else
                                        @foreach ($item['children'] as $child)
                                            <li><a href="{{ $child['url'] }}" class="block rounded-lg px-3 py-2.5 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-800">{{ $child['label'] }}</a></li>
                                        @endforeach
                                    @endif
                                </ul>
                            </li>
                        @else
                            <li>
                                <a href="{{ $item['url'] }}" @class(['block rounded-lg px-3 py-3 font-semibold', 'bg-brand-50 text-brand-700' => $isActive, 'text-slate-800 hover:bg-slate-50' => ! $isActive])>
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </nav>
            <div class="space-y-2 border-t border-slate-200 p-4 text-sm text-slate-600">
                @if (! empty($settings['phone_main']))
                    <p class="flex items-center gap-2"><x-heroicon-o-phone class="size-4 text-brand-600" /> {{ $settings['phone_main'] }}</p>
                @endif
                @if (! empty($settings['email']))
                    <p class="flex items-center gap-2"><x-heroicon-o-envelope class="size-4 text-brand-600" /> {{ $settings['email'] }}</p>
                @endif
                @auth('web')
                    <a href="{{ route('filament.admin.pages.dashboard') }}" class="btn-primary mt-3 w-full"><x-heroicon-o-squares-2x2 class="size-5" /> Panel de administración</a>
                @elseauth('citizen')
                    <a href="{{ route('mesa-partes.account') }}" class="btn-primary mt-3 w-full"><x-heroicon-o-folder-open class="size-5" /> Mis trámites</a>
                @else
                    <a href="{{ route('filament.admin.auth.login') }}" class="btn-secondary mt-3 w-full"><x-heroicon-o-user-circle class="size-5" /> Acceso institucional</a>
                @endauth
            </div>
        </div>
    </div>
</header>
