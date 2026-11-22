<x-layouts.app title="Contacto" description="Canales de atención, consultas y sugerencias ciudadanas.">
    <x-page-header title="Contacto y atención al ciudadano" subtitle="Envíenos sus consultas, sugerencias o felicitaciones. Le responderemos al correo que indique." :breadcrumbs="['Contacto' => null]" />

    <div class="container-site mt-10 grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @if (session('status'))
                <div class="mb-6 flex items-start gap-3 rounded-xl bg-brand-50 p-4 text-brand-900 ring-1 ring-brand-200" role="status">
                    <x-heroicon-o-check-circle class="size-6 shrink-0 text-brand-600" />
                    <p class="font-medium">{{ session('status') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('contact.store') }}" class="card space-y-5 p-6 sm:p-8" novalidate>
                @csrf
                <div class="hidden" aria-hidden="true">
                    <label for="website">No complete este campo</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <fieldset>
                    <legend class="form-label">Tipo de mensaje <span class="text-red-600">*</span></legend>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($types as $type)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium ring-1 ring-slate-300 has-checked:bg-brand-50 has-checked:text-brand-800 has-checked:ring-brand-500">
                                <input type="radio" name="type" value="{{ $type->value }}" class="text-brand-600 focus:ring-brand-500" @checked(old('type', 'consulta') === $type->value)>
                                {{ $type->getLabel() }}
                            </label>
                        @endforeach
                    </div>
                    @error('type')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </fieldset>

                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach ([
                        ['full_name', 'Nombres y apellidos', 'text', true, 'name'],
                        ['document_number', 'DNI / CE', 'text', false, 'off'],
                        ['email', 'Correo electrónico', 'email', true, 'email'],
                        ['phone', 'Teléfono', 'tel', false, 'tel'],
                    ] as [$field, $label, $type, $required, $autocomplete])
                        <div>
                            <label for="{{ $field }}" class="form-label">{{ $label }} @if ($required)<span class="text-red-600">*</span>@endif</label>
                            <input type="{{ $type }}" id="{{ $field }}" name="{{ $field }}" value="{{ old($field) }}" autocomplete="{{ $autocomplete }}"
                                @if ($field === 'document_number') inputmode="numeric" @endif
                                @if ($required) required @endif
                                @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                                @class(['form-input', 'ring-red-500' => $errors->has($field)])>
                            @error($field)<p id="{{ $field }}-error" class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>

                <div>
                    <label for="subject" class="form-label">Asunto <span class="text-red-600">*</span></label>
                    <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required @class(['form-input', 'ring-red-500' => $errors->has('subject')])
                        @error('subject') aria-invalid="true" aria-describedby="subject-error" @enderror>
                    @error('subject')<p id="subject-error" class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="message" class="form-label">Mensaje <span class="text-red-600">*</span></label>
                    <textarea id="message" name="message" rows="6" required maxlength="3000" @class(['form-input', 'ring-red-500' => $errors->has('message')])
                        @error('message') aria-invalid="true" aria-describedby="message-error" @enderror>{{ old('message') }}</textarea>
                    @error('message')<p id="message-error" class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="flex items-start gap-2.5 text-sm text-slate-600">
                        <input type="checkbox" name="privacy" value="1" class="mt-0.5 rounded text-brand-600 focus:ring-brand-500" @checked(old('privacy'))>
                        <span>Acepto la <a href="{{ route('pages.show', 'politica-de-privacidad') }}" target="_blank" class="font-semibold text-brand-700 underline">política de privacidad</a> y el tratamiento de mis datos personales.</span>
                    </label>
                    @error('privacy')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn-primary px-6 py-3">Enviar mensaje <x-heroicon-o-arrow-right class="size-4" /></button>
            </form>
        </div>

        <aside class="space-y-6">
            <div class="card p-6">
                <h2 class="mb-4 font-bold text-slate-900">Sede central</h2>
                <ul class="space-y-3 text-sm text-slate-700">
                    @if (! empty($settings['address']))<li class="flex gap-2.5"><x-heroicon-o-map-pin class="size-5 shrink-0 text-brand-600" /> {{ $settings['address'] }}</li>@endif
                    @foreach (['phone_main' => 'Central', 'phone_director' => 'Dirección', 'phone_procurement' => 'Abastecimiento'] as $key => $label)
                        @if (! empty($settings[$key]))<li class="flex gap-2.5"><x-heroicon-o-phone class="size-5 shrink-0 text-brand-600" /> {{ $label }}: {{ $settings[$key] }}</li>@endif
                    @endforeach
                    @if (! empty($settings['email']))<li class="flex gap-2.5"><x-heroicon-o-envelope class="size-5 shrink-0 text-brand-600" /> <a href="mailto:{{ $settings['email'] }}" class="hover:text-brand-700">{{ $settings['email'] }}</a></li>@endif
                    @if (! empty($settings['schedule']))<li class="flex gap-2.5"><x-heroicon-o-clock class="size-5 shrink-0 text-brand-600" /> {{ $settings['schedule'] }}</li>@endif
                </ul>
            </div>

            @if (! empty($settings['map_embed_url']))
                <div class="card overflow-hidden">
                    <iframe src="{{ $settings['map_embed_url'] }}" class="aspect-square w-full" loading="lazy" title="Mapa de ubicación de la sede central" referrerpolicy="no-referrer"></iframe>
                </div>
            @endif

            <div class="card space-y-3 p-6">
                <h2 class="font-bold text-slate-900">Otros canales</h2>
                @if ($mesaPartesUrl = \App\Support\MesaPartes\MesaPartesConfig::accessUrl())
                    <a href="{{ $mesaPartesUrl }}" @unless (\App\Support\MesaPartes\MesaPartesConfig::enabled()) target="_blank" rel="noopener" @endunless class="btn-secondary w-full"><x-heroicon-o-inbox-arrow-down class="size-5" /> Mesa de partes virtual</a>
                @endif
                <a href="{{ route('complaints.create') }}" class="btn-secondary w-full"><x-heroicon-o-book-open class="size-5" /> Libro de reclamaciones</a>
                @foreach ($agencies as $agency)
                    <a href="{{ route('agencies.show', $agency) }}" class="btn-secondary w-full"><x-heroicon-o-map-pin class="size-5" /> {{ $agency->name }}</a>
                @endforeach
            </div>
        </aside>
    </div>
</x-layouts.app>
