@php
    $institutionalLinks = [
        ['label' => 'Nosotros', 'url' => route('institutional.about'), 'hint' => 'Quiénes somos, misión y visión', 'icon' => 'heroicon-o-building-library'],
        ['label' => 'Organigrama', 'url' => route('institutional.organization-chart'), 'hint' => 'Estructura orgánica', 'icon' => 'heroicon-o-squares-2x2'],
        ['label' => 'Directorio', 'url' => route('institutional.directory'), 'hint' => 'Autoridades y responsables', 'icon' => 'heroicon-o-identification'],
        ['label' => 'Agencias agrarias', 'url' => route('agencies.index'), 'hint' => 'Atención en provincias', 'icon' => 'heroicon-o-map-pin'],
        ['label' => 'Código de ética', 'url' => route('pages.show', 'codigo-de-etica'), 'hint' => 'Principios de la función pública', 'icon' => 'heroicon-o-shield-check'],
    ];

    $agrarianLinks = [
        ['label' => 'Resumen de información agraria', 'url' => route('agrarian-information')],
        ...array_map(fn (array $type): array => ['label' => $type['name'], 'url' => url($type['url'])], $menu['agrarianTypes']),
    ];

    $navigation = [
        ['key' => 'inicio', 'label' => 'Inicio', 'url' => route('home'), 'active' => ['home']],
        ['key' => 'institucional', 'label' => 'Institucional', 'active' => ['institutional.*', 'agencies.*'], 'mega' => true],
        ['key' => 'informacion', 'label' => 'Información agraria', 'active' => ['agrarian-information'], 'children' => $agrarianLinks],
        ['key' => 'noticias', 'label' => 'Noticias', 'url' => route('posts.index'), 'active' => ['posts.*']],
        ['key' => 'documentos', 'label' => 'Documentos', 'active' => ['documents.*'], 'children' => [
            ['label' => 'Normas y resoluciones', 'url' => route('documents.index', ['seccion' => 'normativa'])],
            ['label' => 'Gestión institucional (TUPA, ROF, POI…)', 'url' => route('documents.index', ['seccion' => 'gestion'])],
            ['label' => 'Información agraria', 'url' => route('documents.index', ['seccion' => 'informacion-agraria'])],
            ['label' => 'Todos los documentos', 'url' => route('documents.index')],
        ]],
        ['key' => 'convocatorias', 'label' => 'Convocatorias', 'url' => route('job-postings.index'), 'active' => ['job-postings.*']],
        ['key' => 'transparencia', 'label' => 'Transparencia', 'url' => route('transparency'), 'active' => ['transparency']],
        ['key' => 'contacto', 'label' => 'Contacto', 'url' => route('contact.create'), 'active' => ['contact.*']],
    ];

    $topLinkClasses = 'flex items-center gap-1 px-3 py-3.5 text-sm font-semibold text-white/90 transition hover:bg-brand-700 hover:text-white';
@endphp

{{-- La cabecera completa es "sticky" con desplazamiento negativo: al bajar solo queda visible la franja del menú. --}}
<header class="sticky -top-29 z-40 max-lg:-top-9 print:hidden" x-data="{ mobileOpen: false }" @keydown.escape.window="mobileOpen = false">
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
                @auth
                    <a href="{{ route('filament.admin.pages.dashboard') }}" class="flex items-center gap-1.5 rounded-md bg-gold-400 px-2.5 py-1 font-semibold text-brand-950 hover:bg-gold-300">
                        <x-heroicon-o-squares-2x2 class="size-3.5" />
                        <span class="hidden sm:inline">Panel de administración</span><span class="sm:hidden">Panel</span>
                    </a>
                @else
                    <a href="{{ route('filament.admin.auth.login') }}" class="flex items-center gap-1.5 rounded-md bg-white/10 px-2.5 py-1 font-semibold text-white ring-1 ring-white/20 hover:bg-white/20">
                        <x-heroicon-o-user-circle class="size-3.5" />
                        <span class="hidden sm:inline">Iniciar sesión</span><span class="sm:hidden">Ingresar</span>
                    </a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Identidad y buscador --}}
    <div class="border-b border-slate-200 bg-white">
        <div class="container-site flex h-20 items-center justify-between gap-6">
            <a href="{{ route('home') }}" class="min-w-0" aria-label="Ir al inicio">
                <x-site.logo />
            </a>

            <form method="GET" action="{{ route('search') }}" role="search" class="hidden w-full max-w-md items-center lg:flex">
                <label for="busqueda-cabecera" class="sr-only">Buscar en el portal</label>
                <div class="relative flex-1">
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-slate-400" />
                    <input type="search" id="busqueda-cabecera" name="q" minlength="3" placeholder="Buscar resoluciones, convocatorias, noticias…"
                        class="form-input rounded-r-none py-2.5 pl-10">
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
    <nav class="hidden bg-brand-800 shadow-md lg:block" aria-label="Navegación principal">
        <div class="container-site relative">
            <ul class="flex items-stretch">
                @foreach ($navigation as $item)
                    @php($isActive = request()->routeIs(...$item['active']))
                    @php($activeClasses = $isActive ? 'bg-brand-900 text-white shadow-[inset_0_-3px_0_var(--color-gold-400)]' : '')

                    @if (isset($item['children']) || isset($item['mega']))
                        <li @class(['static' => isset($item['mega']), 'relative' => ! isset($item['mega'])]) data-menu="{{ $item['key'] }}"
                            x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false"
                            @focusout="if (! $el.contains($event.relatedTarget)) open = false">
                            <button type="button" @click="open = ! open" :aria-expanded="open.toString()" class="{{ $topLinkClasses }} {{ $activeClasses }}">
                                {{ $item['label'] }}
                                <x-heroicon-o-chevron-down class="size-4 transition" ::class="open && 'rotate-180'" />
                            </button>

                            @isset($item['mega'])
                                {{-- Menú institucional: igual que el antiguo menú «DRAMDD», con todas las oficinas y sus unidades --}}
                                <div x-cloak x-show="open" x-transition.origin.top class="absolute inset-x-4 top-full z-50 pt-1 sm:inset-x-6 lg:inset-x-8">
                                    <div class="card grid max-h-[75vh] gap-8 overflow-y-auto p-6 shadow-2xl lg:grid-cols-12">
                                        <div class="lg:col-span-3 lg:border-r lg:border-slate-100 lg:pr-6">
                                            <p class="mb-3 text-xs font-bold tracking-wider text-slate-400 uppercase">La institución</p>
                                            <ul class="space-y-1">
                                                @foreach ($institutionalLinks as $link)
                                                    <li>
                                                        <a href="{{ $link['url'] }}" class="flex items-start gap-3 rounded-xl p-2.5 hover:bg-brand-50">
                                                            <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-700">
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
                                            <p class="mb-3 text-xs font-bold tracking-wider text-slate-400 uppercase">Órganos y unidades</p>
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
                                <div x-cloak x-show="open" x-transition.origin.top class="absolute left-0 top-full z-50 w-80 pt-1">
                                    <ul class="card overflow-hidden p-2 shadow-xl">
                                        @foreach ($item['children'] as $child)
                                            <li>
                                                <a href="{{ $child['url'] }}" class="flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-brand-50 hover:text-brand-800">
                                                    {{ $child['label'] }}
                                                    <x-heroicon-o-chevron-right class="size-4 shrink-0 text-slate-300" />
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
                                        <li class="px-3 pt-3 pb-1 text-xs font-bold tracking-wider text-slate-400 uppercase">Órganos y unidades</li>
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
                @auth
                    <a href="{{ route('filament.admin.pages.dashboard') }}" class="btn-primary mt-3 w-full"><x-heroicon-o-squares-2x2 class="size-5" /> Panel de administración</a>
                @else
                    <a href="{{ route('filament.admin.auth.login') }}" class="btn-secondary mt-3 w-full"><x-heroicon-o-user-circle class="size-5" /> Iniciar sesión</a>
                @endauth
            </div>
        </div>
    </div>
</header>
