@php
    use App\Models\Setting;

    $institutionalLinks = [
        'Nosotros' => route('institutional.about'),
        'Organigrama' => route('institutional.organization-chart'),
        'Directorio' => route('institutional.directory'),
        ...$otherPages->mapWithKeys(fn ($other) => [$other->title => route('pages.show', $other)])->all(),
    ];
    $email = Setting::valueOf('email');
    $phone = Setting::valueOf('phone_main');
@endphp

<x-layouts.app :title="$page->title" :description="$page->excerpt">
    <x-page-header :title="$page->title" :subtitle="$page->excerpt"
        :breadcrumbs="$page->show_in_menu ? ['Institucional' => null, $page->title => null] : [$page->title => null]" />

    <div class="container-site mt-10 grid gap-10 lg:grid-cols-12 lg:gap-12">
        <article class="min-w-0 lg:col-span-8">
            <div class="card p-6 sm:p-10">
                <p class="flex items-center gap-2 text-sm text-slate-500">
                    <x-heroicon-o-calendar-days class="size-4 shrink-0" />
                    <span>Actualizado el <time datetime="{{ $page->updated_at->toDateString() }}">{{ $page->updated_at->translatedFormat('j \d\e F \d\e Y') }}</time></span>
                </p>

                @if (filled($outline->html))
                    <div class="prose prose-slate mt-6 max-w-none prose-headings:scroll-mt-28 prose-headings:font-display prose-headings:text-ink prose-h2:mt-10 prose-h2:text-2xl prose-h3:text-lg prose-a:font-semibold prose-a:text-brand-700 hover:prose-a:text-brand-900 prose-blockquote:rounded-r-xl prose-blockquote:border-l-gold-500 prose-blockquote:bg-gold-50 prose-blockquote:py-1 prose-blockquote:font-normal prose-blockquote:not-italic prose-strong:text-ink prose-li:marker:text-brand-600 prose-th:bg-slate-50 prose-img:rounded-xl">
                        {!! $outline->html !!}
                    </div>
                @else
                    <p class="mt-6 text-slate-600">El contenido de esta página se publicará pronto.</p>
                @endif
            </div>

            @if ($documents->isNotEmpty())
                <section class="mt-8" aria-labelledby="documentos-de-la-pagina">
                    <h2 id="documentos-de-la-pagina" class="font-display text-xl font-bold text-ink">Documentos para descargar</h2>
                    <ul class="card mt-4 divide-y divide-slate-100 overflow-hidden">
                        @foreach ($documents as $document)
                            <x-document-row :document="$document" with-context />
                        @endforeach
                    </ul>
                </section>
            @endif
        </article>

        <aside class="lg:col-span-4">
            <div class="space-y-6 lg:sticky lg:top-24">
                @if ($outline->hasIndex())
                    <nav class="card p-5" aria-labelledby="indice-de-la-pagina">
                        <h2 id="indice-de-la-pagina" class="font-display text-base font-bold text-ink">En esta página</h2>
                        <ol class="mt-3 border-l border-slate-200 text-sm">
                            @foreach ($outline->headings as $heading)
                                <li>
                                    <a href="#{{ $heading['id'] }}" @class([
                                        '-ml-px block border-l-2 border-transparent py-1.5 pr-2 text-slate-600 transition-colors hover:border-brand-500 hover:text-brand-800',
                                        'pl-4' => $heading['level'] === 2,
                                        'pl-7 text-[0.8125rem]' => $heading['level'] === 3,
                                    ])>{{ $heading['text'] }}</a>
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @endif

                <nav class="card p-5" aria-labelledby="informacion-institucional">
                    <h2 id="informacion-institucional" class="font-display text-base font-bold text-ink">Información institucional</h2>
                    <ul class="mt-3 space-y-0.5">
                        @foreach ($institutionalLinks as $label => $url)
                            <li>
                                <a href="{{ $url }}" class="group flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-brand-50 hover:text-brand-800">
                                    {{ $label }}
                                    <x-heroicon-m-chevron-right class="size-4 shrink-0 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-brand-600" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="rounded-2xl bg-brand-900 p-6 text-brand-100">
                    <h2 class="font-display text-lg font-bold text-white">¿Tiene alguna consulta?</h2>
                    <p class="mt-1.5 text-sm">Escríbanos y la oficina correspondiente le responderá.</p>
                    @if ($email || $phone)
                        <ul class="mt-4 space-y-2 text-sm">
                            @if ($email)
                                <li class="flex items-center gap-2.5"><x-heroicon-o-envelope class="size-4 shrink-0 text-gold-400" /><a href="mailto:{{ $email }}" class="link-draw break-all hover:text-white">{{ $email }}</a></li>
                            @endif
                            @if ($phone)
                                <li class="flex items-center gap-2.5"><x-heroicon-o-phone class="size-4 shrink-0 text-gold-400" /><a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="link-draw hover:text-white">{{ $phone }}</a></li>
                            @endif
                        </ul>
                    @endif
                    <a href="{{ route('contact.create') }}" class="btn-gold mt-5 w-full">Ir a Contacto</a>
                </div>
            </div>
        </aside>
    </div>
</x-layouts.app>
