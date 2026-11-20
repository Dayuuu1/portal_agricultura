@php
    $quickLinks = [
        'Noticias' => route('posts.index'),
        'Convocatorias CAS' => route('job-postings.index'),
        'Documentos' => route('documents.index'),
        'Información agraria' => route('agrarian-information'),
        'Transparencia' => route('transparency'),
        'Agencias agrarias' => route('agencies.index'),
        'Contacto y sugerencias' => route('contact.create'),
    ];
@endphp

<footer class="relative isolate mt-24 overflow-hidden bg-brand-950 text-brand-100 print:hidden">
    <x-site.contours class="absolute inset-0 -z-10 size-full text-brand-400/10" />
    <div class="h-1 bg-[linear-gradient(90deg,var(--color-gold-400)_0_10rem,var(--color-brand-700)_10rem)]" aria-hidden="true"></div>

    <div class="container-site grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-12 lg:gap-8 lg:py-16">
        <div class="space-y-5 lg:col-span-4">
            <x-site.logo inverse />
            <p class="max-w-sm text-sm leading-relaxed text-brand-200">{{ $settings['site_description'] ?? '' }}</p>
            <div class="flex gap-2">
                @if (! empty($settings['facebook_url']))
                    <a href="{{ $settings['facebook_url'] }}" target="_blank" rel="noopener" aria-label="Facebook (se abre en una pestaña nueva)" class="flex size-11 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/10 transition hover:-translate-y-0.5 hover:bg-white/20">
                        <x-site.social-icon network="facebook" />
                    </a>
                @endif
                @if (! empty($settings['youtube_url']))
                    <a href="{{ $settings['youtube_url'] }}" target="_blank" rel="noopener" aria-label="YouTube (se abre en una pestaña nueva)" class="flex size-11 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/10 transition hover:-translate-y-0.5 hover:bg-white/20">
                        <x-site.social-icon network="youtube" />
                    </a>
                @endif
            </div>
        </div>

        <div class="lg:col-span-3">
            <h2 class="mb-4 font-display text-base font-bold text-white">Contacto</h2>
            <ul class="space-y-3 text-sm">
                @if (! empty($settings['address']))
                    <li class="flex gap-2.5"><x-heroicon-o-map-pin class="mt-0.5 size-4 shrink-0 text-gold-400" /> {{ $settings['address'] }}</li>
                @endif
                @foreach (['phone_main' => 'Central', 'phone_director' => 'Dirección', 'phone_procurement' => 'Abastecimiento'] as $key => $label)
                    @if (! empty($settings[$key]))
                        <li class="flex gap-2.5">
                            <x-heroicon-o-phone class="mt-0.5 size-4 shrink-0 text-gold-400" />
                            <span>{{ $label }}: <a href="tel:{{ preg_replace('/\s+/', '', $settings[$key]) }}" class="link-draw tabular-nums hover:text-white">{{ $settings[$key] }}</a></span>
                        </li>
                    @endif
                @endforeach
                @if (! empty($settings['email']))
                    <li class="flex gap-2.5"><x-heroicon-o-envelope class="mt-0.5 size-4 shrink-0 text-gold-400" /> <a href="mailto:{{ $settings['email'] }}" class="link-draw hover:text-white">{{ $settings['email'] }}</a></li>
                @endif
                @if (! empty($settings['schedule']))
                    <li class="flex gap-2.5"><x-heroicon-o-clock class="mt-0.5 size-4 shrink-0 text-gold-400" /> {{ $settings['schedule'] }}</li>
                @endif
            </ul>
        </div>

        <div class="lg:col-span-2">
            <h2 class="mb-4 font-display text-base font-bold text-white">Enlaces rápidos</h2>
            <ul class="space-y-2.5 text-sm">
                @foreach ($quickLinks as $label => $url)
                    <li><a href="{{ $url }}" class="link-draw hover:text-white">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>

        <div class="lg:col-span-3">
            <h2 class="mb-4 font-display text-base font-bold text-white">Portales del Estado</h2>
            <ul class="space-y-2.5 text-sm">
                @foreach ($footerLinks->get('portales-estado', collect()) as $link)
                    <li>
                        <a href="{{ $link->url }}" target="_blank" rel="noopener" class="group inline-flex items-center gap-1.5 hover:text-white">
                            <span class="link-draw">{{ $link->name }}</span>
                            <x-heroicon-m-arrow-up-right class="size-3.5 opacity-60 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:opacity-100" />
                            <span class="sr-only">(se abre en una pestaña nueva)</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('complaints.create') }}" class="mt-6 inline-flex min-h-11 items-center gap-2.5 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-brand-900 shadow-lg shadow-black/20 transition hover:-translate-y-0.5 hover:bg-gold-300 active:scale-[0.98]">
                <x-heroicon-o-book-open class="size-5" /> Libro de Reclamaciones
            </a>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-site flex flex-col gap-3 py-6 text-xs text-brand-300 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} {{ $settings['site_name'] ?? config('app.name') }}. Todos los derechos reservados.</p>
            <p class="flex flex-wrap gap-x-5 gap-y-2">
                <a href="{{ route('pages.show', 'politica-de-privacidad') }}" class="link-draw hover:text-white">Política de privacidad</a>
                <a href="{{ route('pages.show', 'codigo-de-etica') }}" class="link-draw hover:text-white">Código de ética</a>
                <a href="{{ route('search') }}" class="link-draw hover:text-white">Buscar</a>
                <a href="{{ route('filament.admin.auth.login') }}" class="link-draw hover:text-white">Acceso institucional</a>
            </p>
        </div>
    </div>
</footer>
