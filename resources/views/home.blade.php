<x-layouts.app>
    @php
        $sideImages = $slides->map(fn ($slide): bool => filled($slide->image_url))->values();
        $defaultEyebrow = ($settings['site_acronym'] ?? 'DRDAR').' · Madre de Dios';
        $openPostings = $jobPostings->where('status', \App\Enums\JobPostingStatus::EnCurso)->count();
        $figures = [
            ['documents', 'Documentos públicos', 'heroicon-o-document-text', route('documents.index')],
            ['jobPostings', 'Convocatorias CAS', 'heroicon-o-briefcase', route('job-postings.index')],
            ['posts', 'Noticias publicadas', 'heroicon-o-newspaper', route('posts.index')],
        ];
    @endphp

    {{-- Banner: foto de fondo por diapositiva, velo institucional, curvas de nivel y título que entra palabra por palabra --}}
    <section class="relative isolate overflow-hidden bg-brand-950 text-white"
        x-data="heroCarousel({{ max($slides->count(), 1) }})"
        @focusin="focusEntered($event)" @focusout="focusLeft($event)"
        aria-roledescription="carrusel" aria-label="Destacados">

        @forelse ($slides as $index => $slide)
            <div x-show="active === {{ $index }}" @if ($index > 0) x-cloak @endif
                x-transition:enter="transition-opacity duration-1000" x-transition:enter-start="opacity-0"
                x-transition:leave="transition-opacity duration-1000" x-transition:leave-end="opacity-0"
                class="absolute inset-0 -z-20" aria-hidden="true">
                @if ($slide->background_url)
                    <img src="{{ $slide->background_url }}" alt="" class="hero-background size-full object-cover"
                        @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async">
                @endif
                <div class="absolute inset-0" style="{{ $slide->overlay->gradientStyle() }}"></div>
            </div>
        @empty
            <div class="absolute inset-0 -z-20 bg-[radial-gradient(ellipse_at_80%_10%,var(--color-brand-700),transparent_60%)]" aria-hidden="true"></div>
        @endforelse

        <x-site.contours class="contours-animate absolute inset-0 -z-10 size-full text-white/[0.13] mask-l-from-35% mask-l-to-80%" />

        <div class="container-site relative grid items-center gap-10 pt-14 pb-36 sm:pt-20 lg:min-h-[40rem] lg:grid-cols-12 lg:pt-20 lg:pb-40">
            <div class="lg:col-span-7">
                {{-- Slides share one grid cell, so the hero keeps the height of the longest one and nothing jumps. --}}
                <div class="grid">
                    @forelse ($slides as $index => $slide)
                        <div @class(['col-start-1 row-start-1 transition-[opacity,visibility] duration-500', 'invisible opacity-0' => $index > 0])
                            :class="active === {{ $index }} ? 'is-active visible opacity-100' : 'invisible opacity-0'"
                            role="group" aria-roledescription="diapositiva" aria-label="{{ $index + 1 }} de {{ $slides->count() }}">
                            <p class="enter mb-5 inline-flex items-center gap-3 text-sm font-semibold text-gold-300">
                                <span class="h-0.5 w-8 rounded-full bg-gold-400" aria-hidden="true"></span>
                                {{ $slide->eyebrow ?: $defaultEyebrow }}
                            </p>
                            <h1 class="text-[2.5rem] leading-[1.04] font-extrabold text-balance sm:text-6xl lg:text-[4.1rem]">
                                <span class="sr-only">{{ $slide->title }}</span>
                                <span aria-hidden="true">@foreach (preg_split('/\s+/', trim($slide->title)) as $wordIndex => $word)<span class="hero-word"><span style="--w: {{ $wordIndex }}">{{ $word }}</span></span> @endforeach</span>
                            </h1>
                            @if ($slide->subtitle)
                                <p class="enter mt-6 max-w-xl text-lg text-brand-50/90 sm:text-xl" style="--d: 380ms">{{ $slide->subtitle }}</p>
                            @endif
                            @if ($slide->buttonLink())
                                <div class="enter mt-9 flex flex-wrap gap-3" style="--d: 500ms" @mouseenter="hovering = true" @mouseleave="hovering = false">
                                    <a href="{{ $slide->buttonLink() }}" class="btn-gold group px-5 py-3 text-base">
                                        {{ $slide->button_text ?: 'Ver más' }}
                                        <x-heroicon-m-arrow-right class="size-5 transition duration-300 ease-out-quint group-hover:translate-x-1" />
                                    </a>
                                    <a href="{{ route('contact.create') }}" class="btn-ghost-light px-5 py-3 text-base">Atención al ciudadano</a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="is-active">
                            <h1 class="text-4xl font-extrabold sm:text-5xl">{{ $settings['site_name'] ?? config('app.name') }}</h1>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="relative hidden lg:col-span-5 lg:block">
                @foreach ($slides as $index => $slide)
                    @if ($slide->image_url)
                        <div x-show="active === {{ $index }}" @if ($index > 0) x-cloak @endif
                            x-transition:enter="transition duration-700 ease-out" x-transition:enter-start="translate-y-4 opacity-0"
                            class="media aspect-4/3 w-full rounded-3xl shadow-2xl ring-1 ring-white/20">
                            <img src="{{ $slide->image_url }}" alt="" loading="lazy">
                        </div>
                    @endif
                @endforeach

                {{-- Cifras del portal: cuentan desde cero la primera vez que se ven. --}}
                <div x-show="! sideImages[active]" x-data="{ sideImages: @js($sideImages) }" @if ($sideImages->first()) x-cloak @endif
                    class="enter-now rounded-3xl bg-brand-950/45 p-2 ring-1 ring-white/15 backdrop-blur-md" style="--d: 650ms">
                    <dl class="divide-y divide-white/10">
                        @foreach ($figures as [$key, $label, $icon, $url])
                            <div class="group relative flex items-center justify-between gap-6 rounded-2xl px-6 py-5 transition-colors hover:bg-white/5">
                                <dt class="flex items-center gap-3 text-brand-100">
                                    <span class="flex size-10 items-center justify-center rounded-xl bg-white/10 text-gold-300 ring-1 ring-white/10">
                                        <x-dynamic-component :component="$icon" class="size-5" />
                                    </span>
                                    <a href="{{ $url }}" class="font-medium after:absolute after:inset-0 after:rounded-2xl focus:outline-none focus-visible:after:outline-2 focus-visible:after:outline-gold-400">{{ $label }}</a>
                                </dt>
                                <dd class="font-display text-5xl font-bold tabular-nums" data-count-to="{{ $stats[$key] }}">{{ number_format($stats[$key]) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>

        @if ($slides->count() > 1)
            {{-- Pestañas: la barra dorada indica cuánto falta para la siguiente diapositiva. --}}
            <div class="absolute inset-x-0 bottom-20 sm:bottom-24">
                <div class="container-site flex items-end gap-3 sm:gap-6" @mouseenter="hovering = true" @mouseleave="hovering = false">
                    <div class="grid flex-1 grid-flow-col gap-2 sm:gap-6" style="grid-auto-columns: minmax(0, 1fr)">
                        @foreach ($slides as $index => $slide)
                            <button type="button" @click="go({{ $index }})" class="group pt-3 text-left"
                                :aria-current="active === {{ $index }}" aria-label="Ir a la diapositiva {{ $index + 1 }}: {{ $slide->title }}">
                                <span class="mb-2.5 hidden truncate text-sm font-medium transition-colors sm:block"
                                    :class="active === {{ $index }} ? 'text-white' : 'text-white/60 group-hover:text-white/90'">{{ $slide->eyebrow ?: $slide->title }}</span>
                                <span class="block h-0.5 overflow-hidden rounded-full bg-white/25">
                                    <span class="block h-full origin-left rounded-full bg-gold-400" :style="`transform: scaleX(${fill({{ $index }})})`" style="transform: scaleX({{ $index === 0 ? 1 : 0 }})"></span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <button type="button" @click="prev()" class="flex size-11 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/15 backdrop-blur-sm transition hover:bg-white/20 active:scale-95" aria-label="Diapositiva anterior">
                            <x-heroicon-m-chevron-left class="size-5" />
                        </button>
                        <button type="button" @click="next()" class="flex size-11 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/15 backdrop-blur-sm transition hover:bg-white/20 active:scale-95" aria-label="Diapositiva siguiente">
                            <x-heroicon-m-chevron-right class="size-5" />
                        </button>
                        <button type="button" @click="togglePause()" x-show="autoplays()" class="flex size-11 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/15 backdrop-blur-sm transition hover:bg-white/20 active:scale-95"
                            :aria-label="paused ? 'Reanudar el carrusel' : 'Pausar el carrusel'" :aria-pressed="paused.toString()">
                            <x-heroicon-m-pause x-show="! paused" class="size-4" />
                            <x-heroicon-m-play x-show="paused" x-cloak class="size-4" />
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </section>

    {{-- Accesos rápidos: una sola superficie dividida en tareas --}}
    @php
        $shortcuts = array_values(array_filter([
            ['Convocatorias CAS', 'Procesos de selección y resultados', route('job-postings.index'), 'heroicon-o-briefcase'],
            ['Resoluciones', 'Normas y resoluciones directorales', route('documents.index', ['seccion' => 'normativa']), 'heroicon-o-scale'],
            ['Documentos de gestión', 'TUPA, ROF, MAPRO, POI', route('documents.index', ['seccion' => 'gestion']), 'heroicon-o-folder-open'],
            ['Información agraria', 'Boletines estadísticos y precios', route('agrarian-information'), 'heroicon-o-chart-bar'],
            ['Transparencia', 'Información pública de la entidad', route('transparency'), 'heroicon-o-eye'],
            ['Libro de reclamaciones', 'Registre su reclamo en línea', route('complaints.create'), 'heroicon-o-book-open'],
            ['Consultas y sugerencias', 'Escríbanos, le respondemos', route('contact.create'), 'heroicon-o-chat-bubble-left-right'],
            ($mesaPartesUrl = \App\Support\MesaPartes\MesaPartesConfig::accessUrl())
                ? ['Mesa de partes', 'Presente sus documentos en línea', $mesaPartesUrl, 'heroicon-o-inbox-arrow-down']
                : ['Agencias agrarias', 'Atención en provincias', route('agencies.index'), 'heroicon-o-map-pin'],
        ]));
    @endphp
    <section class="relative z-10 -mt-14 sm:-mt-16" aria-label="Accesos rápidos">
        <div class="container-site">
            <ul class="enter-now grid grid-cols-2 gap-px overflow-hidden rounded-3xl bg-slate-200/70 shadow-lift ring-1 ring-slate-900/[0.06] lg:grid-cols-4" style="--d: 450ms">
                @foreach ($shortcuts as [$label, $hint, $url, $icon])
                    <li class="bg-white">
                        <a href="{{ $url }}" @if (! str_starts_with($url, url('/'))) target="_blank" rel="noopener" @endif
                            class="group relative flex h-full flex-col gap-3 p-4 transition-colors duration-200 hover:bg-brand-50/70 sm:flex-row sm:items-start sm:gap-4 sm:p-6">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100 transition duration-300 ease-out-quint group-hover:-translate-y-0.5 group-hover:bg-brand-600 group-hover:text-white group-hover:ring-brand-600">
                                <x-dynamic-component :component="$icon" class="size-6" />
                            </span>
                            <span class="min-w-0 pr-4">
                                <span class="block text-sm leading-snug font-bold text-ink sm:text-base">{{ $label }}</span>
                                <span class="mt-1 hidden text-sm text-slate-500 sm:block">{{ $hint }}</span>
                            </span>
                            <x-heroicon-m-arrow-up-right class="absolute top-4 right-4 size-4 -translate-x-1 translate-y-1 text-brand-600 opacity-0 transition duration-300 ease-out-quint group-hover:translate-0 group-hover:opacity-100 sm:top-6 sm:right-6" aria-hidden="true" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Noticias: una historia principal a todo color y las siguientes en lista --}}
    <section class="container-site mt-24" aria-labelledby="titulo-noticias" data-reveal>
        <x-section-heading eyebrow="Actualidad" title="Últimas noticias" :link="route('posts.index')" linkLabel="Todas las noticias" id="titulo-noticias" />
        @if ($leadPost)
            <div class="grid gap-6 lg:grid-cols-12">
                <article class="group relative isolate overflow-hidden rounded-3xl bg-brand-900 shadow-lift lg:col-span-7">
                    <div class="media aspect-4/3 sm:aspect-video lg:absolute lg:inset-0 lg:aspect-auto">
                        @if ($leadPost->cover_url)
                            <img src="{{ $leadPost->cover_url }}" alt="" loading="lazy" decoding="async">
                        @endif
                    </div>
                    <div class="pointer-events-none absolute inset-0 bg-linear-to-t from-brand-950 via-brand-950/55 to-transparent" aria-hidden="true"></div>
                    <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8 lg:relative lg:flex lg:min-h-[30rem] lg:flex-col lg:justify-end">
                        <div class="mb-3 flex flex-wrap items-center gap-3 text-sm">
                            @if ($leadPost->category)
                                <span class="rounded-full bg-gold-400 px-2.5 py-0.5 text-xs font-bold text-brand-950">{{ $leadPost->category->name }}</span>
                            @endif
                            <time datetime="{{ $leadPost->published_at->toDateString() }}" class="text-brand-100">{{ $leadPost->published_at->translatedFormat('j \d\e F \d\e Y') }}</time>
                        </div>
                        <h3 class="max-w-2xl text-xl font-bold text-white sm:text-3xl sm:leading-tight">
                            <a href="{{ route('posts.show', $leadPost) }}" class="pointer-events-auto after:absolute after:inset-0 focus:outline-none focus-visible:after:rounded-3xl focus-visible:after:outline-2 focus-visible:after:-outline-offset-4 focus-visible:after:outline-gold-400">
                                <span class="link-draw">{{ $leadPost->title }}</span>
                            </a>
                        </h3>
                        @if ($leadPost->summary)
                            <p class="mt-3 hidden max-w-2xl text-brand-50/85 sm:line-clamp-2">{{ $leadPost->summary }}</p>
                        @endif
                    </div>
                </article>

                <div class="card flex flex-col divide-y divide-slate-100 lg:col-span-5">
                    @foreach ($otherPosts as $post)
                        <article class="group relative flex flex-1 items-center gap-4 p-4 transition-colors first:rounded-t-2xl last:rounded-b-2xl hover:bg-brand-50/60 sm:p-5">
                            <div class="media size-20 shrink-0 rounded-xl sm:size-24">
                                @if ($post->cover_url)
                                    <img src="{{ $post->cover_url }}" alt="" loading="lazy" decoding="async">
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-x-2 text-xs">
                                    @if ($post->category)
                                        <span class="font-semibold text-brand-700">{{ $post->category->name }}</span>
                                        <span class="size-1 rounded-full bg-slate-300" aria-hidden="true"></span>
                                    @endif
                                    <time datetime="{{ $post->published_at->toDateString() }}" class="text-slate-500">{{ $post->published_at->translatedFormat('j M Y') }}</time>
                                </p>
                                <h3 class="mt-1.5 line-clamp-3 text-[0.95rem] leading-snug font-bold text-ink transition-colors group-hover:text-brand-700">
                                    <a href="{{ route('posts.show', $post) }}" class="after:absolute after:inset-0 focus:outline-none focus-visible:after:outline-2 focus-visible:after:-outline-offset-2 focus-visible:after:outline-gold-500">{{ $post->title }}</a>
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
    <section class="container-site mt-24 grid grid-cols-1 gap-12 lg:grid-cols-2 lg:gap-10" data-reveal>
        <div>
            <x-section-heading eyebrow="Empleo público" title="Convocatorias" :link="route('job-postings.index')" linkLabel="Ver convocatorias" />
            @if ($openPostings > 0)
                <p class="-mt-4 mb-5 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-600/20">
                    <span class="status-dot is-live text-emerald-600" aria-hidden="true"></span>
                    {{ $openPostings }} {{ $openPostings === 1 ? 'proceso en curso' : 'procesos en curso' }}
                </p>
            @endif
            <div class="space-y-3">
                @forelse ($jobPostings as $jobPosting)
                    <x-job-posting-item :job-posting="$jobPosting" />
                @empty
                    <x-empty-state icon="heroicon-o-briefcase" title="No hay convocatorias publicadas" />
                @endforelse
            </div>
        </div>
        <div>
            <x-section-heading eyebrow="Normativa y gestión" title="Documentos recientes" :link="route('documents.index')" linkLabel="Ver documentos" />
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
    <section class="container-site mt-24" data-reveal aria-labelledby="titulo-informacion-agraria">
        <div class="relative isolate overflow-hidden rounded-3xl bg-brand-900 px-6 py-10 text-white shadow-lift sm:px-10 lg:px-14 lg:py-14">
            <div class="absolute inset-0 -z-20 bg-[radial-gradient(ellipse_at_0%_100%,var(--color-brand-700),transparent_60%)]" aria-hidden="true"></div>
            <x-site.contours class="absolute inset-0 -z-10 size-full text-gold-300/15" />
            <div class="grid gap-10 lg:grid-cols-12 lg:items-center">
                <div class="lg:col-span-6">
                    <p class="mb-3 inline-flex items-center gap-3 text-sm font-semibold text-gold-300">
                        <span class="h-0.5 w-8 rounded-full bg-gold-400" aria-hidden="true"></span> Estadística agraria regional
                    </p>
                    <h2 id="titulo-informacion-agraria" class="text-3xl font-bold sm:text-4xl">Boletines estadísticos y precios de mercado</h2>
                    <p class="mt-4 max-w-xl text-lg text-brand-100">Producción agrícola y pecuaria, y precios al por menor en los mercados de Madre de Dios, publicados mes a mes.</p>
                    <a href="{{ route('agrarian-information') }}" class="btn-gold group mt-8 px-5 py-3 text-base">
                        <x-heroicon-o-presentation-chart-line class="size-5" /> Ver información agraria
                    </a>
                </div>
                @if ($agrarianDocuments->isNotEmpty())
                    <div class="lg:col-span-6">
                        <h3 class="mb-3 text-sm font-semibold text-brand-100">Últimas publicaciones</h3>
                        <ul class="divide-y divide-white/10 rounded-2xl bg-brand-950/40 ring-1 ring-white/10 backdrop-blur-sm">
                            @foreach ($agrarianDocuments as $document)
                                <li class="group relative flex items-center gap-4 px-5 py-4 transition-colors first:rounded-t-2xl last:rounded-b-2xl hover:bg-white/5">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-gold-400/15 text-gold-300 ring-1 ring-gold-300/20">
                                        <x-heroicon-o-chart-bar class="size-5" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <a href="{{ route('documents.show', $document) }}" class="line-clamp-2 font-semibold text-white after:absolute after:inset-0 focus:outline-none focus-visible:after:outline-2 focus-visible:after:-outline-offset-2 focus-visible:after:outline-gold-400">{{ $document->title }}</a>
                                        @if ($document->issued_at)
                                            <span class="text-xs text-brand-200">Publicado el <time datetime="{{ $document->issued_at->toDateString() }}">{{ $document->issued_at->translatedFormat('j M Y') }}</time></span>
                                        @endif
                                    </span>
                                    <x-heroicon-m-arrow-right class="size-4 shrink-0 text-gold-300 transition duration-300 ease-out-quint group-hover:translate-x-1" aria-hidden="true" />
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Agencias + atención --}}
    <section class="container-site mt-24 grid grid-cols-1 gap-6 lg:grid-cols-3" data-reveal>
        <div class="card p-6 sm:p-8 lg:col-span-2">
            <p class="eyebrow">Atención descentralizada</p>
            <h2 class="text-2xl font-bold text-ink">Agencias agrarias</h2>
            <p class="mt-2 text-slate-600">Atención y asistencia técnica cerca de los productores, en cada provincia de la región.</p>
            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                @foreach ($agencies as $agency)
                    <li>
                        <a href="{{ route('agencies.show', $agency) }}" class="group flex min-h-16 items-center justify-between gap-3 rounded-2xl bg-brand-50 p-4 font-semibold text-brand-900 ring-1 ring-brand-100 transition duration-300 ease-out-quint hover:-translate-y-0.5 hover:bg-brand-100 hover:shadow-lift">
                            <span class="flex items-center gap-3">
                                <span class="flex size-10 items-center justify-center rounded-xl bg-white text-brand-700 shadow-sm">
                                    <x-heroicon-o-map-pin class="size-5" />
                                </span>
                                <span>{{ $agency->name }} <span class="block text-sm font-normal text-brand-700">Provincia de {{ $agency->province }}</span></span>
                            </span>
                            <x-heroicon-m-arrow-right class="size-4 transition duration-300 ease-out-quint group-hover:translate-x-1" />
                        </a>
                    </li>
                @endforeach
                <li>
                    <a href="{{ route('agencies.index') }}" class="group flex min-h-16 items-center justify-center gap-2 rounded-2xl p-4 text-sm font-semibold text-brand-700 ring-1 ring-brand-200 ring-inset transition hover:bg-brand-50">
                        Ver todas las agencias <x-heroicon-m-arrow-right class="size-4 transition group-hover:translate-x-1" />
                    </a>
                </li>
            </ul>
        </div>
        <div class="relative isolate overflow-hidden rounded-2xl bg-brand-950 p-6 text-brand-100 shadow-lift sm:p-8">
            <x-site.contours class="absolute inset-0 -z-10 size-full text-brand-400/15" />
            <h2 class="text-2xl font-bold text-white">Atención al ciudadano</h2>
            <ul class="mt-5 space-y-4 text-sm">
                @if (! empty($settings['address']))
                    <li class="flex gap-3"><x-heroicon-o-map-pin class="size-5 shrink-0 text-gold-400" /> {{ $settings['address'] }}</li>
                @endif
                @if (! empty($settings['phone_main']))
                    <li class="flex gap-3"><x-heroicon-o-phone class="size-5 shrink-0 text-gold-400" /> <a href="tel:{{ preg_replace('/\s+/', '', $settings['phone_main']) }}" class="link-draw tabular-nums hover:text-white">{{ $settings['phone_main'] }}</a></li>
                @endif
                @if (! empty($settings['email']))
                    <li class="flex gap-3"><x-heroicon-o-envelope class="size-5 shrink-0 text-gold-400" /> <a href="mailto:{{ $settings['email'] }}" class="link-draw break-all hover:text-white">{{ $settings['email'] }}</a></li>
                @endif
                @if (! empty($settings['schedule']))
                    <li class="flex gap-3"><x-heroicon-o-clock class="size-5 shrink-0 text-gold-400" /> {{ $settings['schedule'] }}</li>
                @endif
            </ul>
            <a href="{{ route('contact.create') }}" class="btn-gold mt-7 w-full">Enviar una consulta</a>
        </div>
    </section>

    {{-- Enlaces de interés --}}
    @if ($interestLinks->isNotEmpty())
        <section class="container-site mt-24 mb-4" aria-labelledby="titulo-enlaces" data-reveal>
            <x-link-carousel :links="$interestLinks" eyebrow="Sector agrario" title="Enlaces de interés" heading-id="titulo-enlaces" />
        </section>
    @endif
</x-layouts.app>
