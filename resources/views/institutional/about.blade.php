<x-layouts.app title="Nosotros" :description="$page?->excerpt">
    <x-page-header title="Nosotros" :subtitle="$page?->excerpt" :breadcrumbs="['Institucional' => null, 'Nosotros' => null]" />

    <div class="container-site mt-10 space-y-16">
        <section class="grid gap-8 lg:grid-cols-5">
            <div class="card p-6 sm:p-10 lg:col-span-3">
                <h2 class="mb-4 text-2xl font-extrabold text-slate-900">¿Quiénes somos?</h2>
                <div class="prose prose-slate max-w-none">
                    {!! str($page?->body ?? '')->sanitizeHtml() !!}
                </div>
            </div>
            <div class="grid gap-6 lg:col-span-2">
                @foreach (['mission' => ['Misión', 'heroicon-o-sparkles'], 'vision' => ['Visión', 'heroicon-o-eye']] as $key => [$label, $icon])
                    @if (! empty($settings[$key]))
                        <div @class(['relative isolate overflow-hidden rounded-2xl p-6 sm:p-8', 'bg-brand-900 text-white' => $loop->first, 'bg-gold-100 text-brand-950 ring-1 ring-gold-300/50' => $loop->last])>
                            <x-site.contours @class(['absolute inset-0 -z-10 size-full', 'text-brand-300/20' => $loop->first, 'text-gold-500/25' => $loop->last]) />
                            <h2 class="mb-2 flex items-center gap-2 text-lg font-extrabold"><x-dynamic-component :component="$icon" class="size-6" /> {{ $label }}</h2>
                            <p @class(['leading-relaxed', 'text-brand-50' => $loop->first])>{{ $settings[$key] }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

        @if ($director)
            <section class="card flex flex-col items-center gap-6 p-6 sm:flex-row sm:p-8">
                <div class="flex size-28 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-100 ring-4 ring-brand-50">
                    @if ($director->head_photo_url)
                        <img src="{{ $director->head_photo_url }}" alt="{{ $director->head_name }}" class="size-full object-cover">
                    @else
                        <x-heroicon-o-user class="size-12 text-brand-600" />
                    @endif
                </div>
                <div class="text-center sm:text-left">
                    <p class="eyebrow mb-1">{{ $director->head_title ?? 'Director(a) Regional' }}</p>
                    <p class="font-display text-2xl font-extrabold text-slate-900">{{ $director->head_name ?: 'Por designar' }}</p>
                    <p class="mt-1 text-slate-600">{{ $director->description }}</p>
                </div>
            </section>
        @endif

        @if ($lineUnits->isNotEmpty())
            <section>
                <x-section-heading eyebrow="Qué hacemos" title="Nuestras áreas de trabajo" :link="route('institutional.organization-chart')" linkLabel="Ver organigrama" />
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($lineUnits as $unit)
                        <a href="{{ route('institutional.unit', $unit) }}" class="card card-link group p-6">
                            <span class="mb-4 flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100 transition duration-300 ease-out-quint group-hover:-translate-y-0.5 group-hover:bg-brand-600 group-hover:text-white">
                                <x-heroicon-o-building-office-2 class="size-6" />
                            </span>
                            <h3 class="font-bold text-slate-900 group-hover:text-brand-700">{{ $unit->name }}</h3>
                            <p class="mt-2 text-sm text-slate-600">{{ $unit->description }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
