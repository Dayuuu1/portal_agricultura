<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use App\Models\Setting;
use App\Models\User;
use App\Support\Seo\SearchEngines;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Every value of «Datos del portal» in one form, organised by topic and saved at once.
 * Values are still stored one per row in `settings`, so each change keeps its own entry in the activity log.
 */
class EditSettings extends Page
{
    protected static string $resource = SettingResource::class;

    protected static ?string $title = 'Datos del portal';

    protected static ?string $breadcrumb = 'Datos del portal';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * Names shorter than the stored labels, which still carry setup notes.
     */
    private const LABELS = [
        'site_name' => 'Nombre oficial de la entidad',
        'site_short_name' => 'Nombre corto',
        'site_acronym' => 'Siglas',
        'parent_entity' => 'Entidad a la que pertenece',
        'site_description' => 'Descripción breve',
        'site_logo' => 'Logo',
        'site_favicon' => 'Ícono de la pestaña del navegador',
        'mission' => 'Misión',
        'vision' => 'Visión',
        'map_embed_url' => 'Mapa de ubicación',
        'libro_reclamaciones_url' => 'Libro de reclamaciones en GOB.PE',
        'google_site_verification' => 'Google Search Console',
        'bing_site_verification' => 'Bing Webmaster Tools',
    ];

    /**
     * Where each value appears on the public site.
     */
    private const HINTS = [
        'site_name' => 'Título del navegador, pie de página, correos y resultados de Google.',
        'site_short_name' => 'Texto en negrita junto al logo y nombre del panel de administración.',
        'site_acronym' => 'Etiqueta destacada en la portada.',
        'parent_entity' => 'Barra superior del portal y línea pequeña sobre el nombre.',
        'site_description' => 'Pie de página y descripción para buscadores.',
        'site_logo' => 'PNG o WEBP con fondo transparente, de al menos 120 px de alto; máximo 1 MB. Si lo quita, vuelve el logo original.',
        'site_favicon' => 'Imagen cuadrada en PNG de al menos 192 × 192 px; máximo 1 MB. Si la quita, vuelve el ícono original.',
        'mission' => 'Página «Nosotros».',
        'vision' => 'Página «Nosotros».',
        'address' => 'Pie de página, portada y Contacto.',
        'phone_main' => 'Barra superior, pie de página y Contacto.',
        'phone_director' => 'Pie de página y Contacto.',
        'phone_procurement' => 'Pie de página y Contacto.',
        'email' => 'Barra superior, pie de página y Contacto.',
        'schedule' => 'Pie de página, portada y Contacto.',
        'map_embed_url' => 'En Google Maps: Compartir → Insertar un mapa → copie solo la dirección que está dentro de src="…".',
        'facebook_url' => 'Ícono en la cabecera y el pie.',
        'youtube_url' => 'Ícono en la cabecera y el pie.',
        'mesa_partes_url' => 'Acceso directo en la portada y en Contacto.',
        'transparencia_url' => 'Menú «Transparencia» y pie de página.',
        'libro_reclamaciones_url' => 'Opcional: si la entidad está registrada en la plataforma del Estado, se muestra junto al formulario del portal.',
        'complaints_email' => 'Cada reclamo registrado en el portal llega a este correo con sus adjuntos.',
        'sigaweb_url' => 'Sistema interno; no se muestra al público.',
        'google_site_verification' => 'Pegue el código de la «Etiqueta HTML» (o la etiqueta completa: <meta name="google-site-verification" …>).',
        'bing_site_verification' => 'Opcional. Código de la etiqueta «msvalidate.01» que muestra Bing al agregar el sitio.',
    ];

    private const PREFIX_ICONS = [
        'address' => Heroicon::OutlinedMapPin,
        'phone_main' => Heroicon::OutlinedPhone,
        'phone_director' => Heroicon::OutlinedPhone,
        'phone_procurement' => Heroicon::OutlinedPhone,
        'email' => Heroicon::OutlinedEnvelope,
        'complaints_email' => Heroicon::OutlinedEnvelope,
        'schedule' => Heroicon::OutlinedClock,
    ];

    /**
     * Values the portal cannot do without.
     */
    private const REQUIRED = ['site_name', 'site_short_name'];

    /**
     * Keys placed in each tab; any other stored value lands in «Otros datos».
     */
    private const TABS = [
        'identidad' => ['site_name', 'site_short_name', 'site_acronym', 'parent_entity', 'site_description', 'site_logo', 'site_favicon'],
        'mision-vision' => ['mission', 'vision'],
        'contacto' => ['address', 'phone_main', 'phone_director', 'phone_procurement', 'email', 'schedule', 'map_embed_url'],
        'redes' => ['facebook_url', 'youtube_url'],
        'servicios' => ['mesa_partes_url', 'transparencia_url', 'libro_reclamaciones_url', 'complaints_email', 'sigaweb_url'],
        'buscadores' => ['google_site_verification', 'bing_site_verification'],
    ];

    /**
     * The page is the whole module, so «Datos del portal › Datos del portal» would only repeat the title.
     *
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getSubheading(): string
    {
        return 'Información de la entidad que aparece en todo el portal. Lo que deje vacío no se muestra al público.';
    }

    public function mount(): void
    {
        $this->form->fill($this->storedValues());
    }

    public function canEdit(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->can('update', Setting::class);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Datos del portal')
                    ->persistTabInQueryString('pestana')
                    ->tabs(array_filter([
                        $this->tab('identidad', 'Identidad', Heroicon::OutlinedIdentification, [
                            Grid::make(3)->schema([
                                Group::make([
                                    $this->section('Nombre de la entidad', 'Cómo se presenta la entidad en el portal, el panel y los correos.', ['site_name', 'site_short_name', 'site_acronym', 'parent_entity', 'site_description'], columns: 2),
                                    Section::make('Vista previa')
                                        ->description('Así se verán la pestaña del navegador y el encabezado del portal. Se publica al guardar.')
                                        ->icon(Heroicon::OutlinedEye)
                                        ->schema([
                                            View::make('filament.settings.identity-preview')
                                                ->viewData(fn (Get $get): array => [
                                                    'siteName' => $get('site_name'),
                                                    'shortName' => $get('site_short_name'),
                                                    'parentEntity' => $get('parent_entity'),
                                                    'logoUrl' => $this->previewUrl($get('site_logo'), 'site_logo'),
                                                    'faviconUrl' => $this->previewUrl($get('site_favicon'), 'site_favicon'),
                                                ]),
                                        ]),
                                ])->columnSpan(['default' => 3, 'lg' => 2]),
                                $this->section('Logo e ícono', 'Imágenes propias de la entidad.', ['site_logo', 'site_favicon'])
                                    ?->columnSpan(['default' => 3, 'lg' => 1]),
                            ]),
                        ]),
                        $this->tab('mision-vision', 'Misión y visión', Heroicon::OutlinedFlag, [
                            Callout::make('Textos referenciales')
                                ->description('La misión o la visión todavía muestran el texto de ejemplo con el que se instaló el portal. Reemplácelos por los textos oficiales de la entidad.')
                                ->warning()
                                ->visible(fn (Get $get): bool => collect(Setting::REFERENCE_TEXTS)->contains(fn (string $text, string $key): bool => trim((string) $get($key)) === $text)),
                            $this->section(null, 'Se muestran en la página «Nosotros».', ['mission', 'vision']),
                        ]),
                        $this->tab('contacto', 'Contacto', Heroicon::OutlinedPhone, [
                            $this->section('Datos de contacto', 'Barra superior, pie de página y página de Contacto.', ['address', 'phone_main', 'phone_director', 'phone_procurement', 'email', 'schedule'], columns: 2),
                            $this->section('Ubicación', null, ['map_embed_url']),
                        ]),
                        $this->tab('redes', 'Redes sociales', Heroicon::OutlinedChatBubbleLeftRight, [
                            $this->section(null, 'Pegue la dirección completa de cada página. Las que deje vacías no muestran su ícono.', ['facebook_url', 'youtube_url']),
                        ]),
                        $this->tab('servicios', 'Servicios en línea', Heroicon::OutlinedGlobeAlt, [
                            $this->section('Enlaces para la ciudadanía', null, ['mesa_partes_url', 'transparencia_url', 'libro_reclamaciones_url'], columns: 1),
                            $this->section('Libro de reclamaciones del portal', null, ['complaints_email']),
                            $this->section('Uso interno', null, ['sigaweb_url']),
                        ]),
                        $this->tab('buscadores', 'Buscadores', Heroicon::OutlinedMagnifyingGlassCircle, [
                            Callout::make('Esta copia del portal no se muestra en los buscadores')
                                ->description('Fuera del servidor de producción, robots.txt y las páginas piden a Google que no indexe nada (así una copia de pruebas nunca aparece en los resultados). En el servidor publicado configure APP_ENV=production y APP_URL con el dominio en https://.')
                                ->warning()
                                ->visible(! SearchEngines::indexingAllowed()),
                            Callout::make('Cómo publicar el portal en Google')
                                ->description($this->searchConsoleSteps())
                                ->icon(Heroicon::OutlinedInformationCircle)
                                ->info(),
                            $this->section('Verificación de propiedad', 'Demuestra a Google y a Bing que la entidad administra este sitio. Se publica al guardar, en todas las páginas.', ['google_site_verification', 'bing_site_verification']),
                        ]),
                        $this->otherSettingsTab(),
                    ])),
            ])
            ->statePath('data')
            ->disabled(! $this->canEdit());
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Guardar cambios')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                        Action::make('discard')
                            ->label('Descartar cambios')
                            ->color('gray')
                            ->action(fn () => $this->form->fill($this->storedValues())),
                    ])
                        ->sticky()
                        ->visible($this->canEdit()),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewSite')
                ->label('Ver portal')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(route('home'))
                ->openUrlInNewTab(),
        ];
    }

    public function save(): void
    {
        abort_unless($this->canEdit(), 403);

        $state = $this->form->getState();
        $changed = 0;

        foreach ($this->settings() as $setting) {
            if (! array_key_exists($setting->key, $state)) {
                continue;
            }

            $value = is_string($state[$setting->key]) ? trim($state[$setting->key]) : $state[$setting->key];
            $value = filled($value) ? (string) $value : null;

            if ($value !== $setting->value) {
                $setting->update(['value' => $value]);
                $changed++;
            }
        }

        $this->form->fill($this->storedValues());

        Notification::make()
            ->title($changed > 0 ? 'Cambios guardados' : 'No había cambios por guardar')
            ->body($changed > 0 ? 'El portal ya muestra la información actualizada.' : null)
            ->success()
            ->send();
    }

    /**
     * Stored rows, read once per request: every field of the form looks up its own.
     *
     * @return Collection<string, Setting>
     */
    private function settings(): Collection
    {
        return once(fn (): Collection => Setting::query()->portalData()->orderBy('sort_order')->orderBy('id')->get()->keyBy('key'));
    }

    /**
     * @return array<string, string|null>
     */
    private function storedValues(): array
    {
        return Setting::query()->portalData()->pluck('value', 'key')->all();
    }

    /**
     * @param  list<Component|null>  $components
     */
    private function tab(string $key, string $label, Heroicon $icon, array $components): ?Tab
    {
        $components = array_values(array_filter($components));

        if ($components === []) {
            return null;
        }

        // toBase(): on an Eloquent collection only() filters by primary key, not by the setting key.
        $settings = $this->settings()->toBase()->only(self::TABS[$key] ?? []);
        $empty = $settings->filter(fn (Setting $setting): bool => blank($setting->value) && $setting->type !== 'image')->count();

        return Tab::make($label)
            ->id($key)
            ->icon($icon)
            ->badge($empty > 0 ? $empty : null)
            ->badgeColor('warning')
            ->badgeTooltip($empty > 0 ? 'Datos sin completar: no se muestran en el portal' : null)
            ->schema($components);
    }

    /**
     * Section holding the given settings; null when none of them exist yet (e.g. before running migrations).
     *
     * @param  list<string>  $keys
     */
    private function section(?string $heading, ?string $description, array $keys, int $columns = 1): ?Section
    {
        $fields = array_values(array_filter(array_map(fn (string $key): ?Component => $this->field($key), $keys)));

        if ($fields === []) {
            return null;
        }

        return Section::make($heading)
            ->description($description)
            ->columns(['default' => 1, 'md' => $columns])
            ->schema($fields);
    }

    private function field(string $key): ?Component
    {
        $setting = $this->settings()->get($key);

        if ($setting === null) {
            return null;
        }

        $label = self::LABELS[$key] ?? $setting->label;
        $hint = self::HINTS[$key] ?? null;
        $isPreviewed = in_array($key, ['site_name', 'site_short_name', 'parent_entity'], true);

        return match ($setting->type) {
            'image' => FileUpload::make($key)
                ->label($label)
                ->helperText($hint)
                ->image()
                ->acceptedFileTypes(['image/png', 'image/webp', 'image/jpeg'])
                ->maxSize(1024)
                ->disk('public')
                ->directory(Setting::IMAGE_DIRECTORY)
                ->visibility('public')
                ->imagePreviewHeight('110')
                // This page has no record to compare with, so the stored file is the only path accepted back.
                ->preventFilePathTampering(allowFilePathUsing: fn (string $file): bool => $file === $setting->value)
                ->live(),
            'textarea' => Textarea::make($key)
                ->label($label)
                ->helperText($hint)
                ->rows(4)
                ->autosize()
                ->columnSpanFull(),
            default => TextInput::make($key)
                ->label($label)
                ->helperText($hint)
                ->placeholder(match ($setting->type) {
                    'url' => 'https://…',
                    'email' => 'correo@entidad.gob.pe',
                    default => null,
                })
                ->maxLength(500)
                ->url($setting->type === 'url')
                ->email($setting->type === 'email')
                ->prefixIcon(self::PREFIX_ICONS[$key] ?? ($setting->type === 'url' ? Heroicon::OutlinedLink : null))
                ->required(in_array($key, self::REQUIRED, true))
                ->columnSpan(in_array($key, ['site_name', 'parent_entity', 'address', 'map_embed_url'], true) || $setting->type === 'url' ? 'full' : 1)
                ->live(onBlur: $isPreviewed),
        };
    }

    /**
     * Steps to register the portal in Google Search Console, with this site's own addresses.
     */
    private function searchConsoleSteps(): HtmlString
    {
        $sitemap = e(route('seo.sitemap'));
        $robots = e(route('seo.robots'));
        $home = e(route('home'));

        return new HtmlString(<<<HTML
            <ol class="mt-1 list-decimal space-y-1 ps-5">
                <li>Entre a <a href="https://search.google.com/search-console" target="_blank" rel="noopener" class="font-semibold underline">Google Search Console</a> con la cuenta de Google de la entidad y pulse «Agregar propiedad».</li>
                <li>Elija «Prefijo de la URL» y escriba <strong>{$home}</strong>.</li>
                <li>En «Otros métodos de verificación» elija «Etiqueta HTML», copie el código, péguelo abajo y guarde. Luego pulse «Verificar» en Search Console.</li>
                <li>En el menú «Sitemaps» de Search Console envíe <a href="{$sitemap}" target="_blank" rel="noopener" class="font-semibold underline">{$sitemap}</a>. Se actualiza solo cada vez que publica contenido.</li>
                <li>Las reglas para los buscadores están en <a href="{$robots}" target="_blank" rel="noopener" class="underline">{$robots}</a>. Google tarda de unos días a algunas semanas en mostrar las páginas.</li>
            </ol>
            HTML);
    }

    /**
     * Values added later (or by another module) that have no place in the tabs yet.
     */
    private function otherSettingsTab(): ?Tab
    {
        $placed = array_merge(...array_values(self::TABS));
        $others = $this->settings()->keys()->reject(fn (string $key): bool => in_array($key, $placed, true))->values()->all();

        return $others === [] ? null : Tab::make('Otros datos')
            ->id('otros')
            ->icon(Heroicon::OutlinedEllipsisHorizontalCircle)
            ->schema(array_filter([$this->section(null, null, $others)]));
    }

    /**
     * Image shown in the preview: the file just chosen, the stored one, or the original bundled with the portal.
     */
    private function previewUrl(mixed $state, string $key): string
    {
        if ($state instanceof TemporaryUploadedFile) {
            return rescue(fn (): string => $state->temporaryUrl(), asset(Setting::DEFAULT_IMAGES[$key]), report: false);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return is_string($state) && filled($state) && $disk->exists($state)
            ? $disk->url($state)
            : asset(Setting::DEFAULT_IMAGES[$key]);
    }
}
