<x-layouts.app>
    {{-- Hero / carrusel: foto de fondo por banner, velo verde institucional e imagen lateral o cifras --}}
    @php
        $sideImages = $slides->map(fn ($slide): bool => filled($slide->image_url))->values();
        $defaultEyebrow = ($settings['site_acronym'] ?? 'DRDAR').' · Madre de Dios';
    @endphp
    <section class="relative isolate overflow-hidden bg-brand-900 text-white"
        x-data="{ active: 0, total: {{ max($slides->count(), 1) }}, timer: null, paused: false, sideImages: @js($sideImages),
            next() { this.active = (this.active + 1) % this.total },
            prev() { this.active = (this.active - 1 + this.total) % this.total },
            reducedMotion() { return window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('a11y-no-motion') },
            start() { this.stop(); if (this.total > 1 && ! this.paused && ! this.reducedMotion()) { this.timer = setInterval(() => this.next(), 7000) } },
            stop() { clearInterval(this.timer) },
            togglePause() { this.paused = ! this.paused; this.paused ? this.stop() : this.start() } }"
        x-init="start()" @mouseenter="stop()" @mouseleave="start()" @focusin="stop()" @accesibilidad:detener-animaciones.window="paused = true; stop()"
        aria-roledescription="carrusel" aria-label="Destacados">

        @forelse ($slides as $index => $slide)
            <div x-show="active === {{ $index }}" @if ($index > 0) x-cloak @endif
                x-transition:enter="transition-opacity duration-700" x-transition:enter-start="opacity-0"
                x-transition:leave="transition-opacity duration-700" x-transition:leave-end="opacity-0"
                class="absolute inset-0 -z-10" aria-hidden="true">
                @if ($slide->background_url)
                    <img src="{{ $slide->background_url }}" alt="" class="hero-background size-full object-cover"
                        @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async">
                @else
                    <div class="bg-pattern absolute inset-0"></div>
                    <div class="absolute -top-32 -right-32 size-112 rounded-full bg-brand-500/30 blur-3xl"></div>
                    <div class="absolute -bottom-40 -left-20 size-96 rounded-full bg-gold-400/20 blur-3xl"></div>
                @endif
                <div class="absolute inset-0" style="{{ $slide->overlay->gradientStyle() }}"></div>
            </div>
        @empty
            <div class="bg-pattern absolute inset-0 -z-10" aria-hidden="true"></div>
        @endforelse

        <div class="container-site relative grid min-h-112 items-center py-14 lg:min-h-136 lg:grid-cols-12 lg:gap-10 lg:py-20">
            <div class="lg:col-span-7">
                {{-- Slides share one grid cell, so the hero keeps the height of the longest one and nothing jumps. --}}
                <div class="grid">
                @forelse ($slides as $index => $slide)
                    <div @class(['col-start-1 row-start-1 transition duration-500', 'invisible opacity-0' => $index > 0])
                        :class="active === {{ $index }} ? 'visible opacity-100 translate-y-0 delay-150' : 'invisible opacity-0 translate-y-3'"
                        role="group" aria-roledescription="diapositiva" aria-label="{{ $index + 1 }} de {{ $slides->count() }}">
                        <p class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold tracking-wider text-gold-300 uppercase ring-1 ring-white/20 backdrop-blur-sm">
                            <x-heroicon-o-sparkles class="size-4" /> {{ $slide->eyebrow ?: $defaultEyebrow }}
                        </p>
                        <h1 class="text-4xl font-extrabold tracking-tight text-balance drop-shadow-md sm:text-5xl lg:text-[3.4rem] lg:leading-[1.08]">{{ $slide->title }}</h1>
                        @if ($slide->subtitle)
                            <p class="mt-5 max-w-2xl text-lg text-brand-50 drop-shadow">{{ $slide->subtitle }}</p>
                        @endif
                        @if ($slide->button_url)
                            <div class="mt-8 flex flex-wrap gap-3">
                                <a href="{{ $slide->button_url }}" class="btn-gold px-5 py-3">{{ $slide->button_text ?: 'Ver más' }} <x-heroicon-o-arrow-right class="size-4" /></a>
                                <a href="{{ route('contact.create') }}" class="btn bg-white/10 px-5 py-3 text-white ring-1 ring-white/25 backdrop-blur-sm hover:bg-white/20">Atención al ciudadano</a>
                            </div>
                        @endif
                    </div>
                @empty
                    <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">{{ $settings['site_name'] ?? config('app.name') }}</h1>
                @endforelse
                </div>

                @if ($slides->count() > 1)
                    <div class="mt-10 flex items-center gap-3">
                        <button type="button" @click="prev()" class="flex size-9 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/15 backdrop-blur-sm hover:bg-white/20" aria-label="Diapositiva anterior">
                            <x-heroicon-o-chevron-left class="size-5" />
                        </button>
                        <div class="flex gap-2">
                            @foreach ($slides as $index => $slide)
                                <button type="button" @click="active = {{ $index }}" class="h-2 rounded-full transition-all"
                                    :class="active === {{ $index }} ? 'w-8 bg-gold-400' : 'w-2 bg-white/40 hover:bg-white/60'"
                                    aria-label="Ir a la diapositiva {{ $index + 1 }}" :aria-current="active === {{ $index }}"></button>
                            @endforeach
                        </div>
                        <button type="button" @click="next()" class="flex size-9 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/15 backdrop-blur-sm hover:bg-white/20" aria-label="Diapositiva siguiente">
                            <x-heroicon-o-chevron-right class="size-5" />
                        </button>
                        <button type="button" @click="togglePause()" class="ml-1 flex size-9 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/15 backdrop-blur-sm hover:bg-white/20"
                            :aria-label="paused ? 'Reanudar el carrusel' : 'Pausar el carrusel'" :aria-pressed="paused.toString()">
                            <x-heroicon-o-pause x-show="! paused" class="size-4" />
                            <x-heroicon-o-play x-show="paused" x-cloak class="size-4" />
                        </button>
                    </div>
                @endif
            </div>

            <div class="relative mt-12 hidden lg:col-span-5 lg:mt-0 lg:block">
                @foreach ($slides as $index => $slide)
                    @if ($slide->image_url)
                        <img x-show="active === {{ $index }}" @if ($index > 0) x-cloak @endif src="{{ $slide->image_url }}" alt=""
                            x-transition:enter="transition duration-500" x-transition:enter-start="opacity-0 scale-95"
                            class="aspect-4/3 w-full rounded-3xl object-cover shadow-2xl ring-1 ring-white/20" loading="lazy">
                    @endif
                @endforeach
                <div x-show="! sideImages[active]" @if ($sideImages->first()) x-cloak @endif class="grid grid-cols-2 gap-4">
                    @foreach ([['documents', 'Documentos publicados', 'heroicon-o-document-text'], ['jobPostings', 'Convocatorias', 'heroicon-o-briefcase'], ['posts', 'Noticias', 'heroicon-o-newspaper']] as [$key, $label, $icon])
                        <div @class(['rounded-2xl bg-brand-950/35 p-6 ring-1 ring-white/15 backdrop-blur-md', 'col-span-2' => $loop->last])>
                            <x-dynamic-component :component="$icon" class="mb-3 size-7 text-gold-300" />
                            <p class="font-display text-4xl font-extrabold">{{ number_format($stats[$key]) }}</p>
                            <p class="text-sm text-brand-100">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Accesos rápidos --}}
    @php
        $shortcuts = array_values(array_filter([
            ['Convocatorias CAS', 'Procesos de selección y resultados', route('job-postings.index'), 'heroicon-o-briefcase'],
            ['Resoluciones', 'Normas y resoluciones directorales', route('documents.index', ['seccion' => 'normativa']), 'heroicon-o-scale'],
            ['Documentos de gestión', 'TUPA, ROF, MAPRO, POI', route('documents.index', ['seccion' => 'gestion']), 'heroicon-o-folder-open'],
            ['Información agraria', 'Boletines estadísticos y precios', route('agrarian-information'), 'heroicon-o-chart-bar'],
            ['Transparencia', 'Información pública de la entidad', route('transparency'), 'heroicon-o-eye'],
            ['Libro de reclamaciones', 'Registre su reclamo en línea', route('complaints.create'), 'heroicon-o-book-open'],
            ['Consultas y sugerencias', 'Escríbanos, le respondemos', route('contact.create'), 'heroicon-o-chat-bubble-left-right'],
            ! empty($settings['mesa_partes_url'])
                ? ['Mesa de partes', 'Presente sus documentos en línea', $settings['mesa_partes_url'], 'heroicon-o-inbox-arrow-down']
                : ['Agencias agrarias', 'Atención en provincias', route('agencies.index'), 'heroicon-o-map-pin'],
        ]));
    @endphp
    <section class="relative z-10 -mt-8" aria-label="Accesos rápidos">
        <div class="container-site">
            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                @foreach ($shortcuts as [$label, $hint, $url, $icon])
                    <li>
                        <a href="{{ $url }}" @if (! str_starts_with($url, url('/'))) target="_blank" rel="noopener" @endif
                            class="card group flex h-full flex-col items-start gap-3 p-4 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-brand-300 sm:flex-row sm:p-5">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 transition group-hover:bg-brand-600 group-hover:text-white">
                                <x-dynamic-component :component="$icon" class="size-6" />
                            </span>
                            <span>
                                <span class="block text-sm font-bold text-slate-900 sm:text-base">{{ $label }}</span>
                                <span class="mt-0.5 hidden text-xs text-slate-500 sm:block">{{ $hint }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Noticias --}}
    <section class="container-site mt-20" aria-labelledby="titulo-noticias">
        <x-section-heading eyebrow="Actualidad" title="Últimas noticias" :link="route('posts.index')" linkLabel="Todas las noticias" id="titulo-noticias" />
        @if ($leadPost)
            <div class="grid gap-6 lg:grid-cols-5">
                <x-post-card :post="$leadPost" large class="lg:col-span-3" />
                <div class="grid gap-4 lg:col-span-2">
                    @foreach ($otherPosts as $post)
                        <article class="card group flex gap-4 p-4 transition hover:shadow-md">
                            <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-linear-to-br from-brand-500 to-brand-700">
                                @if ($post->cover_url)
                                    <img src="{{ $post->cover_url }}" alt="" class="size-full object-cover" loading="lazy">
                                @else
                                    <x-heroicon-o-newspaper class="size-8 text-white/50" />
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-brand-600">{{ $post->category?->name }} · {{ $post->published_at->translatedFormat('j M Y') }}</p>
                                <h3 class="mt-1 line-clamp-3 text-sm font-bold text-slate-900 group-hover:text-brand-700">
                                    <a href="{{ route('posts.show', $post) }}">{{ $post->title }}</a>
                                </h3>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @else
            <x-empty-state icon="heroicon-o-newspaper" title="Aún no hay noticias publicadas" />
        @endif
    </section>

    {{-- Convocatorias y documentos --}}
    <section class="container-site mt-20 grid grid-cols-1 gap-10 lg:grid-cols-2">
        <div>
            <x-section-heading eyebrow="Empleo público" title="Convocatorias" :link="route('job-postings.index')" />
            <div class="space-y-3">
                @forelse ($jobPostings as $jobPosting)
                    <x-job-posting-item :job-posting="$jobPosting" />
                @empty
                    <x-empty-state icon="heroicon-o-briefcase" title="No hay convocatorias publicadas" />
                @endforelse
            </div>
        </div>
        <div>
            <x-section-heading eyebrow="Normativa y gestión" title="Documentos recientes" :link="route('documents.index')" />
            @if ($documents->isNotEmpty())
                <ul class="card divide-y divide-slate-100 overflow-hidden">
                    @foreach ($documents as $document)
                        <x-document-item :document="$document" />
                    @endforeach
                </ul>
            @else
                <x-empty-state icon="heroicon-o-document-text" title="No hay documentos publicados" />
            @endif
        </div>
    </section>

    {{-- Información agraria --}}
    <section class="container-site mt-20">
        <div class="relative overflow-hidden rounded-3xl bg-linear-to-r from-gold-500 to-gold-400 px-6 py-10 sm:px-12">
            <div class="absolute -right-10 -bottom-16 size-64 rounded-full bg-white/20" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-sm font-bold tracking-wider text-brand-900/70 uppercase">Estadística agraria regional</p>
                    <h2 class="mt-1 text-2xl font-extrabold text-brand-950 sm:text-3xl">Boletines estadísticos y precios de mercado</h2>
                    <p class="mt-2 text-brand-950/80">Consulte la información de producción agrícola y pecuaria, y los precios al por menor en los mercados de Madre de Dios.</p>
                </div>
                <a href="{{ route('agrarian-information') }}" class="btn shrink-0 bg-brand-900 px-5 py-3 text-white hover:bg-brand-950">
                    <x-heroicon-o-presentation-chart-line class="size-5" /> Ver información agraria
                </a>
            </div>
        </div>
    </section>

    {{-- Agencias + atención --}}
    <section class="container-site mt-20 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2">
            <h2 class="flex items-center gap-2 text-xl font-extrabold text-slate-900"><x-heroicon-o-map-pin class="size-6 text-brand-600" /> Agencias agrarias</h2>
            <p class="mt-1 text-sm text-slate-600">Atención descentralizada para los productores de la región.</p>
            <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                @foreach ($agencies as $agency)
                    <li>
                        <a href="{{ route('agencies.show', $agency) }}" class="flex items-center justify-between gap-3 rounded-xl bg-brand-50 p-4 font-semibold text-brand-900 hover:bg-brand-100">
                            <span>{{ $agency->name }} <span class="block text-xs font-normal text-brand-700">Provincia de {{ $agency->province }}</span></span>
                            <x-heroicon-o-arrow-right class="size-4" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card bg-brand-900 p-6 text-brand-100 ring-0">
            <h2 class="text-xl font-extrabold text-white">Atención al ciudadano</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @if (! empty($settings['address']))<li class="flex gap-2.5"><x-heroicon-o-map-pin class="size-5 shrink-0 text-gold-400" /> {{ $settings['address'] }}</li>@endif
                @if (! empty($settings['phone_main']))<li class="flex gap-2.5"><x-heroicon-o-phone class="size-5 shrink-0 text-gold-400" /> {{ $settings['phone_main'] }}</li>@endif
                @if (! empty($settings['schedule']))<li class="flex gap-2.5"><x-heroicon-o-clock class="size-5 shrink-0 text-gold-400" /> {{ $settings['schedule'] }}</li>@endif
            </ul>
            <a href="{{ route('contact.create') }}" class="btn-gold mt-6 w-full">Escríbanos</a>
        </div>
    </section>

    {{-- Enlaces de interés --}}
    @if ($interestLinks->isNotEmpty())
        <section class="container-site mt-20 mb-4" aria-labelledby="titulo-enlaces">
            <x-link-carousel :links="$interestLinks" eyebrow="Sector agrario" title="Enlaces de interés" heading-id="titulo-enlaces" />
        </section>
    @endif
</x-layouts.app>
