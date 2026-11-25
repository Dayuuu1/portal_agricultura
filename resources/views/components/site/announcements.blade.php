{{--
    Ventana de anuncios al ingresar al portal. Qué anuncios corresponden a la página lo decide App\Support\SiteAnnouncements;
    cuáles ya cerró el visitante, resources/js/announcements.js. Tres diseños: imagen y texto, afiche (la imagen completa)
    y comunicado (sin imagen, con la franja institucional).
--}}
@if ($announcements->isNotEmpty())
    @php
        $entries = $announcements->map(fn (\App\Models\Announcement $announcement): array => [
            'id' => $announcement->id,
            'version' => $announcement->version(),
            'frequency' => $announcement->frequency->value,
            'track' => route('announcements.track', $announcement),
        ])->all();
        $origin = $settings['site_acronym'] ?? 'DRDAR';
    @endphp

    <div x-data="announcements(@js($entries), @js($preview), @js(csrf_token()))" class="print:hidden">
        <dialog x-ref="dialog" @close="closed()" @click.self="close()"
            @keydown.arrow-right="next()" @keydown.arrow-left="previous()"
            :aria-labelledby="`anuncio-${entries[pending[index]]?.id}-titulo`"
            class="announcement-dialog m-auto max-h-[calc(100dvh-1.5rem)] w-[calc(100%-1.5rem)] max-w-xl overflow-visible bg-transparent p-0 text-slate-700">
            <div class="relative flex max-h-[calc(100dvh-1.5rem)] flex-col overflow-hidden rounded-3xl bg-white shadow-2xl shadow-brand-950/40 ring-1 ring-black/5">
                @if ($preview)
                    <p class="flex items-center gap-2 bg-gold-400 py-2.5 pr-16 pl-5 text-sm font-semibold text-brand-950">
                        <x-heroicon-m-eye class="size-4 shrink-0" />
                        Vista previa: solo usted ve esta ventana.
                    </p>
                @endif

                {{-- Focus starts on the message (read from its title), not on the close button; Tab then reaches the buttons and the close button last. --}}
                <div autofocus tabindex="-1" class="min-h-0 flex-1 overflow-y-auto overscroll-contain focus:outline-none">
                    @foreach ($announcements as $index => $announcement)
                        @php
                            $kind = $announcement->kind;
                            $image = $announcement->image_url;
                            $text = $announcement->text;
                            $link = $announcement->link();
                            $external = $link !== null && preg_match('#^https?://#i', $link) === 1 && ! str_starts_with($link, url('/'));
                            $titleId = 'anuncio-'.$announcement->id.'-titulo';
                        @endphp

                        <article x-show="isCurrent({{ $index }})" @if ($index > 0) x-cloak @endif data-announcement="{{ $index }}" aria-labelledby="{{ $titleId }}">
                            @if ($image && $announcement->layout === \App\Enums\AnnouncementLayout::Poster)
                                {{-- Afiche: la pieza ya diseñada se ve completa, sin recortes. --}}
                                <div class="flex justify-center bg-brand-950">
                                    @if ($link)
                                        <a href="{{ $link }}" @click="follow()" @if ($external) target="_blank" rel="noopener" @endif class="block focus-visible:-outline-offset-4">
                                            <img src="{{ $image }}" alt="{{ $announcement->title }}" loading="lazy" decoding="async" class="max-h-[min(72dvh,52rem)] w-auto max-w-full object-contain">
                                        </a>
                                    @else
                                        <img src="{{ $image }}" alt="{{ $announcement->title }}" loading="lazy" decoding="async" class="max-h-[min(72dvh,52rem)] w-auto max-w-full object-contain">
                                    @endif
                                </div>
                                <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                    <div class="min-w-0">
                                        <p class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $kind->isGold() ? 'text-gold-600' : 'text-brand-700' }}">
                                            <x-dynamic-component :component="$kind->iconComponent()" class="size-4" />
                                            {{ $kind->getLabel() }}
                                        </p>
                                        <h2 id="{{ $titleId }}" class="mt-1 text-lg leading-snug font-bold text-ink">{{ $announcement->title }}</h2>
                                        @if ($text)
                                            <p class="sr-only">{{ $text }}</p>
                                        @endif
                                    </div>
                                    @if ($link)
                                        <a href="{{ $link }}" @click="follow()" @if ($external) target="_blank" rel="noopener" @endif class="btn-primary group shrink-0 px-5">
                                            {{ $announcement->buttonLabel() }}
                                            @if ($external)
                                                <span class="sr-only">(se abre en otra pestaña)</span>
                                            @endif
                                            <x-heroicon-m-arrow-right class="size-4 transition duration-300 ease-out-quint group-hover:translate-x-0.5" />
                                        </a>
                                    @endif
                                </div>
                            @else
                                @if ($image)
                                    {{-- Imagen y texto: foto horizontal arriba, mensaje debajo. --}}
                                    <div class="media aspect-video">
                                        <img src="{{ $image }}" alt="" loading="lazy" decoding="async">
                                    </div>
                                @else
                                    {{-- Comunicado: franja institucional con las curvas de nivel del portal. --}}
                                    <div class="relative isolate overflow-hidden bg-brand-900 px-6 pt-7 pb-8 text-white sm:px-8">
                                        <x-site.contours class="absolute inset-0 -z-10 size-full text-white/10 mask-l-from-20%" />
                                        <div class="flex items-center gap-3 pr-10">
                                            <span @class([
                                                'flex size-11 shrink-0 items-center justify-center rounded-2xl',
                                                'bg-gold-400 text-brand-950' => $kind->isGold(),
                                                'bg-white/10 text-gold-300 ring-1 ring-white/20 ring-inset' => ! $kind->isGold(),
                                            ])>
                                                <x-dynamic-component :component="$kind->iconComponent()" class="size-6" />
                                            </span>
                                            <p class="text-sm leading-tight">
                                                <span class="block font-semibold text-gold-300">{{ $kind->getLabel() }}</span>
                                                <span class="text-brand-100/80">{{ $origin }} · Madre de Dios</span>
                                            </p>
                                        </div>
                                        <h2 id="{{ $titleId }}" class="mt-5 text-2xl leading-tight font-extrabold text-white sm:text-[1.75rem]">{{ $announcement->title }}</h2>
                                    </div>
                                @endif

                                <div class="px-6 pt-6 pb-7 sm:px-8 sm:pb-8">
                                    @if ($image)
                                        <p class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $kind->badgeClasses() }}">
                                            <x-dynamic-component :component="$kind->iconComponent()" class="size-4" />
                                            {{ $kind->getLabel() }}
                                        </p>
                                        <h2 id="{{ $titleId }}" class="mt-4 text-2xl leading-tight font-extrabold text-ink sm:text-[1.75rem]">{{ $announcement->title }}</h2>
                                    @endif

                                    @if ($text)
                                        <p @class(['text-base leading-relaxed whitespace-pre-line text-slate-600', 'mt-3' => $image])>{{ $text }}</p>
                                    @endif

                                    <div class="mt-7 flex flex-col gap-2.5 sm:flex-row sm:items-center">
                                        @if ($link)
                                            <a href="{{ $link }}" @click="follow()" @if ($external) target="_blank" rel="noopener" @endif class="btn-primary group px-5">
                                                {{ $announcement->buttonLabel() }}
                                                @if ($external)
                                                    <span class="sr-only">(se abre en otra pestaña)</span>
                                                @endif
                                                <x-heroicon-m-arrow-right class="size-4 transition duration-300 ease-out-quint group-hover:translate-x-0.5" />
                                            </a>
                                        @endif
                                        <button type="button" @click="close()" class="btn text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                                            {{ $link ? 'Ahora no' : 'Entendido' }}
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>

                @if ($announcements->count() > 1)
                    {{-- Varios anuncios en una sola ventana, nunca ventanas apiladas. --}}
                    <nav x-show="pending.length > 1" aria-label="Anuncios" class="flex items-center justify-between gap-3 border-t border-slate-100 bg-paper/60 px-4 py-2.5">
                        <div class="flex items-center gap-1.5">
                            <template x-for="(item, position) in pending" :key="item">
                                <button type="button" @click="go(position)" :aria-current="position === index ? 'true' : 'false'"
                                    class="h-2 rounded-full transition-all duration-300 ease-out-quint"
                                    :class="position === index ? 'w-6 bg-brand-600' : 'w-2 bg-slate-300 hover:bg-slate-400'">
                                    <span class="sr-only" x-text="`Ver anuncio ${position + 1}`"></span>
                                </button>
                            </template>
                        </div>
                        <div class="flex items-center gap-1">
                            <p class="mr-2 text-sm font-medium text-slate-500 tabular-nums" aria-live="polite" x-text="`${index + 1} de ${pending.length}`"></p>
                            <button type="button" @click="previous()" class="flex size-9 items-center justify-center rounded-full text-slate-600 transition hover:bg-white hover:text-brand-800 hover:shadow-sm">
                                <span class="sr-only">Anuncio anterior</span>
                                <x-heroicon-m-chevron-left class="size-5" />
                            </button>
                            <button type="button" @click="next()" class="flex size-9 items-center justify-center rounded-full text-slate-600 transition hover:bg-white hover:text-brand-800 hover:shadow-sm">
                                <span class="sr-only">Anuncio siguiente</span>
                                <x-heroicon-m-chevron-right class="size-5" />
                            </button>
                        </div>
                    </nav>
                @endif

                <button type="button" @click="close()"
                    class="absolute top-3 right-3 z-20 flex size-10 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-md ring-1 ring-slate-900/10 backdrop-blur transition hover:scale-105 hover:bg-white hover:text-brand-900">
                    <span class="sr-only">Cerrar anuncio</span>
                    <x-heroicon-o-x-mark class="size-5" />
                </button>
            </div>
        </dialog>
    </div>
@endif
