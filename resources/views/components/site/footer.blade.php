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

<footer class="mt-20 bg-brand-950 text-brand-100 print:hidden">
    <div class="flex h-1.5" aria-hidden="true">
        <span class="flex-1 bg-brand-500"></span><span class="flex-1 bg-gold-400"></span><span class="flex-1 bg-brand-700"></span>
    </div>

    <div class="container-site grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-4">
        <div class="space-y-4">
            <x-site.logo inverse />
            <p class="text-sm leading-relaxed text-brand-200">{{ $settings['site_description'] ?? '' }}</p>
            <div class="flex gap-3">
                @if (! empty($settings['facebook_url']))
                    <a href="{{ $settings['facebook_url'] }}" target="_blank" rel="noopener" aria-label="Facebook" class="flex size-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20">
                        <x-site.social-icon network="facebook" />
                    </a>
                @endif
                @if (! empty($settings['youtube_url']))
                    <a href="{{ $settings['youtube_url'] }}" target="_blank" rel="noopener" aria-label="YouTube" class="flex size-9 items-center justify-center rounded-full bg-white/10 hover:bg-white/20">
                        <x-site.social-icon network="youtube" />
                    </a>
                @endif
            </div>
        </div>

        <div>
            <h2 class="mb-4 text-sm font-bold tracking-wider text-white uppercase">Contacto</h2>
            <ul class="space-y-3 text-sm">
                @if (! empty($settings['address']))
                    <li class="flex gap-2.5"><x-heroicon-o-map-pin class="mt-0.5 size-4 shrink-0 text-gold-400" /> {{ $settings['address'] }}</li>
                @endif
                @foreach (['phone_main' => 'Central', 'phone_director' => 'Dirección', 'phone_procurement' => 'Abastecimiento'] as $key => $label)
                    @if (! empty($settings[$key]))
                        <li class="flex gap-2.5"><x-heroicon-o-phone class="mt-0.5 size-4 shrink-0 text-gold-400" /> {{ $label }}: {{ $settings[$key] }}</li>
                    @endif
                @endforeach
                @if (! empty($settings['email']))
                    <li class="flex gap-2.5"><x-heroicon-o-envelope class="mt-0.5 size-4 shrink-0 text-gold-400" /> <a href="mailto:{{ $settings['email'] }}" class="hover:text-white">{{ $settings['email'] }}</a></li>
                @endif
                @if (! empty($settings['schedule']))
                    <li class="flex gap-2.5"><x-heroicon-o-clock class="mt-0.5 size-4 shrink-0 text-gold-400" /> {{ $settings['schedule'] }}</li>
                @endif
            </ul>
        </div>

        <div>
            <h2 class="mb-4 text-sm font-bold tracking-wider text-white uppercase">Enlaces rápidos</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($quickLinks as $label => $url)
                    <li><a href="{{ $url }}" class="hover:text-white hover:underline">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="mb-4 text-sm font-bold tracking-wider text-white uppercase">Portales del Estado</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($footerLinks->get('portales-estado', collect()) as $link)
                    <li>
                        <a href="{{ $link->url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 hover:text-white hover:underline">
                            {{ $link->name }} <x-heroicon-o-arrow-top-right-on-square class="size-3.5" />
                        </a>
                    </li>
                @endforeach
                <li class="pt-2">
                    <a href="{{ route('complaints.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-bold text-brand-900 hover:bg-gold-300">
                        <x-heroicon-o-book-open class="size-5" /> Libro de Reclamaciones
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-site flex flex-col gap-3 py-6 text-xs text-brand-300 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} {{ $settings['site_name'] ?? config('app.name') }}. Todos los derechos reservados.</p>
            <p class="flex flex-wrap gap-x-4 gap-y-1">
                <a href="{{ route('pages.show', 'politica-de-privacidad') }}" class="hover:text-white">Política de privacidad</a>
                <a href="{{ route('pages.show', 'codigo-de-etica') }}" class="hover:text-white">Código de ética</a>
                <a href="{{ route('search') }}" class="hover:text-white">Buscar</a>
                <a href="{{ route('filament.admin.auth.login') }}" class="hover:text-white">Acceso institucional</a>
            </p>
        </div>
    </div>
</footer>
