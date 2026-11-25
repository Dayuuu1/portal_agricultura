<?php

namespace App\Filament\Resources\Announcements;

use App\Enums\AnnouncementFrequency;
use App\Enums\AnnouncementKind;
use App\Enums\AnnouncementLayout;
use App\Enums\AnnouncementPlacement;
use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Announcements\Pages\AnnouncementResults;
use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Resources\Announcements\Widgets\AnnouncementActivityChart;
use App\Filament\Resources\Announcements\Widgets\AnnouncementDailyStats;
use App\Filament\Resources\Announcements\Widgets\AnnouncementStatsOverview;
use App\Filament\Resources\Slides\SlideResource;
use App\Models\Announcement;
use App\Models\AnnouncementStat;
use App\Models\Post;
use App\Models\Setting;
use App\Rules\SafeLink;
use App\Support\SiteAnnouncements;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use UnitEnum;

class AnnouncementResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = Announcement::class;

    protected static ?string $slug = 'anuncios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'anuncio';

    protected static ?string $pluralModelLabel = 'anuncios';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    /**
     * Each announcement has two tabs: its form and what visitors did with it.
     */
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            EditAnnouncement::class,
            AnnouncementResults::class,
        ]);
    }

    /**
     * Full-page form: what the visitor reads and the image on the left, the live preview and the publication
     * settings on the right, so the preview stays beside the fields while writing.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Section::make('Mensaje')
                            ->description('Lo que leerá el visitante. Breve y directo: la ventana interrumpe la navegación.')
                            ->icon(Heroicon::OutlinedPencilSquare)
                            ->columns(2)
                            ->schema(static::messageFields()),
                        Section::make('Imagen')
                            ->description('Opcional. Sin imagen, el anuncio se muestra como comunicado oficial, con la franja verde institucional.')
                            ->icon(Heroicon::OutlinedPhoto)
                            ->columns(['default' => 1, 'xl' => 2])
                            ->schema(static::imageFields()),
                    ]),
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Section::make('Vista previa')
                            ->description('Así verá la ventana quien ingrese al portal.')
                            ->icon(Heroicon::OutlinedEye)
                            ->schema([
                                View::make('filament.announcements.preview')->viewData(fn (Get $get): array => static::previewData($get)),
                            ]),
                        Section::make('Publicación')
                            ->description('Cuándo, dónde y cada cuánto aparece.')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->schema(static::publicationFields()),
                    ]),
            ]);
    }

    /**
     * @return list<Field>
     */
    private static function messageFields(): array
    {
        return [
            ToggleButtons::make('kind')
                ->label('Tipo de anuncio')
                ->helperText('Define la etiqueta, el ícono y el color. «Aviso importante» y «Destacado» van en dorado.')
                ->options(AnnouncementKind::class)
                ->default(AnnouncementKind::Notice)
                ->inline()
                ->required()
                ->live()
                ->columnSpanFull(),
            Select::make('post_id')
                ->label('Destacar una noticia (opcional)')
                ->helperText('Lo que deje vacío (texto, imagen y botón) se toma de la noticia. El anuncio espera a que la noticia esté publicada.')
                ->relationship('post', 'title', fn (Builder $query) => $query->latest('published_at'))
                ->getOptionLabelFromRecordUsing(fn (Post $post): string => Str::limit($post->title, 90).($post->published_at ? ' · '.$post->published_at->format('d/m/Y') : ''))
                ->searchable()
                ->preload()
                ->optionsLimit(20)
                ->live()
                ->afterStateUpdated(function (Set $set, Get $get, mixed $state): void {
                    if (filled($state) && blank($get('title')) && $post = Post::query()->find($state)) {
                        $set('title', Str::limit($post->title, 147));
                    }
                })
                ->columnSpanFull(),
            TextInput::make('title')
                ->label('Título')
                ->helperText('Directo y breve: idealmente menos de 70 caracteres.')
                ->required()
                ->maxLength(150)
                ->live(onBlur: true)
                ->columnSpanFull(),
            Textarea::make('body')
                ->label('Texto')
                ->helperText(fn (Get $get): string => filled($get('post_id'))
                    ? 'Si lo deja vacío se usa el resumen de la noticia.'
                    : 'Dos a cuatro líneas: qué, cuándo y dónde. Se respetan los saltos de línea.')
                ->rows(4)
                ->maxLength(600)
                ->live(onBlur: true)
                ->columnSpanFull(),
            TextInput::make('button_text')
                ->label('Texto del botón')
                ->placeholder(fn (Get $get): string => filled($get('post_id')) && blank($get('button_url')) ? 'Leer la noticia' : 'Ver más')
                ->maxLength(40)
                ->live(onBlur: true),
            TextInput::make('button_url')
                ->label('Enlace del botón')
                ->placeholder('/convocatorias')
                ->helperText(fn (Get $get): string => filled($get('post_id'))
                    ? 'Vacío: el botón lleva a la noticia.'
                    : 'Ruta del portal (/convocatorias) o dirección completa (https://…). Sin enlace no hay botón.')
                ->maxLength(255)
                ->rule(new SafeLink)
                ->live(onBlur: true),
        ];
    }

    /**
     * @return list<Field>
     */
    private static function imageFields(): array
    {
        return [
            ToggleButtons::make('layout')
                ->label('Diseño')
                ->options(AnnouncementLayout::class)
                ->default(AnnouncementLayout::Card)
                ->inline()
                ->required()
                ->live()
                ->helperText(fn (Get $get): string => static::layoutState($get)->getDescription()),
            FileUpload::make('image')
                ->label('Imagen')
                ->helperText(fn (Get $get): string => static::layoutState($get) === AnnouncementLayout::Poster
                    ? 'El afiche se ve completo, vertical u horizontal. JPG, PNG o WEBP de hasta 5 MB; se reduce a 1600 px.'
                    : 'Foto horizontal 16:9. Recomendado: 1200 × 675 px, JPG o WEBP, hasta 5 MB. Si destaca una noticia y no sube una, se usa su portada.')
                ->image()
                ->acceptedFileTypes(SlideResource::IMAGE_TYPES)
                ->imageEditor()
                ->imageEditorAspectRatioOptions([null, '16:9', '4:5', '1:1'])
                ->automaticallyResizeImagesMode('contain')
                ->automaticallyResizeImagesToWidth('1600')
                ->automaticallyResizeImagesToHeight('1600')
                ->automaticallyUpscaleImagesWhenResizing(false)
                ->disk('public')
                ->directory('anuncios')
                ->maxSize(5120)
                ->live(),
        ];
    }

    /**
     * @return list<Field>
     */
    private static function publicationFields(): array
    {
        return [
            Toggle::make('is_active')
                ->label('Activo')
                ->helperText('Desactívelo para ocultarlo sin borrarlo.')
                ->default(true),
            DateTimePicker::make('starts_at')
                ->label('Mostrar desde')
                ->helperText('Opcional. Vacío: desde que lo guarde.')
                ->seconds(false),
            DateTimePicker::make('ends_at')
                ->label('Mostrar hasta')
                ->helperText('Recomendado: un anuncio vencido que sigue apareciendo da mala imagen.')
                ->seconds(false)
                ->after('starts_at'),
            Radio::make('placement')
                ->label('Dónde aparece')
                ->options(AnnouncementPlacement::class)
                ->default(AnnouncementPlacement::Home)
                ->required(),
            Radio::make('frequency')
                ->label('Cada cuánto lo ve un mismo visitante')
                ->options(AnnouncementFrequency::class)
                ->default(AnnouncementFrequency::Once)
                ->required(),
            TextInput::make('sort_order')
                ->label('Orden')
                ->helperText('Si hay varios anuncios a la vez, se muestran juntos en una sola ventana, en este orden.')
                ->numeric()
                ->minValue(0)
                ->default(fn (): int => (int) Announcement::query()->max('sort_order') + 1),
        ];
    }

    /**
     * Form state in the shape of the preview view (and of what the portal will print).
     *
     * @return array{kind: AnnouncementKind, layout: AnnouncementLayout, title: ?string, text: ?string, image: ?string, buttonLabel: ?string, origin: string}
     */
    public static function previewData(Get $get): array
    {
        $post = filled($get('post_id')) ? Post::query()->find($get('post_id')) : null;
        $kind = $get('kind');
        $hasLink = filled($get('button_url')) || $post !== null;

        return [
            'kind' => $kind instanceof AnnouncementKind ? $kind : (AnnouncementKind::tryFrom((string) $kind) ?? AnnouncementKind::Notice),
            'layout' => static::layoutState($get),
            'title' => $get('title'),
            'text' => filled($get('body')) ? $get('body') : $post?->summary,
            'image' => SlideResource::previewUrl($get('image')) ?? ($post?->cover_image ? Storage::disk('public')->url($post->cover_image) : null),
            'buttonLabel' => $hasLink ? ($get('button_text') ?: ($post && blank($get('button_url')) ? 'Leer la noticia' : 'Ver más')) : null,
            'origin' => (Setting::valueOf('site_acronym') ?? 'DRDAR').' · Madre de Dios',
        ];
    }

    private static function layoutState(Get $get): AnnouncementLayout
    {
        $layout = $get('layout');

        return $layout instanceof AnnouncementLayout ? $layout : (AnnouncementLayout::tryFrom((string) $layout) ?? AnnouncementLayout::Card);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('post')
                ->withSum('stats as views_total', 'views')
                ->withSum('stats as clicks_total', 'clicks'))
            ->emptyStateIcon(Heroicon::OutlinedMegaphone)
            ->emptyStateHeading('Aún no hay anuncios')
            ->emptyStateDescription('Cree el primero con «Nuevo anuncio»: un comunicado, un evento, un afiche o una noticia que quiera destacar.')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->state(fn (Announcement $record): ?string => $record->image ?: $record->post?->cover_image)
                    ->disk('public')
                    ->imageWidth(96)
                    ->imageHeight(54)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                    ->placeholder('Sin imagen'),
                TextColumn::make('title')
                    ->label('Anuncio')
                    ->searchable()
                    ->weight('semibold')
                    ->wrap()
                    ->description(fn (Announcement $record): ?string => $record->post
                        ? 'Destaca la noticia: '.Str::limit($record->post->title, 70)
                        : Str::limit((string) $record->body, 100)),
                TextColumn::make('kind')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('visibility')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Announcement $record): string => $record->visibility()['label'])
                    ->color(fn (Announcement $record): string => $record->visibility()['color'])
                    ->description(fn (Announcement $record): ?string => match (true) {
                        $record->starts_at && $record->ends_at => $record->starts_at->format('d/m/Y').' – '.$record->ends_at->format('d/m/Y'),
                        (bool) $record->starts_at => 'Desde '.$record->starts_at->format('d/m/Y H:i'),
                        (bool) $record->ends_at => 'Hasta '.$record->ends_at->format('d/m/Y H:i'),
                        default => 'Sin fecha de fin',
                    }),
                TextColumn::make('views_total')
                    ->label('Vistas')
                    ->state(fn (Announcement $record): int => (int) $record->views_total)
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->url(fn (Announcement $record): string => static::getUrl('results', ['record' => $record])),
                TextColumn::make('clicks_total')
                    ->label('Clics')
                    ->state(fn (Announcement $record): int => (int) $record->clicks_total)
                    ->numeric()
                    ->alignEnd()
                    ->sortable()
                    ->description(fn (Announcement $record): string => AnnouncementStat::formatRate(AnnouncementStat::rate((int) $record->clicks_total, (int) $record->views_total)))
                    ->url(fn (Announcement $record): string => static::getUrl('results', ['record' => $record])),
                TextColumn::make('placement')
                    ->label('Dónde y cada cuánto')
                    ->color('gray')
                    ->description(fn (Announcement $record): string => $record->frequency->getLabel())
                    ->toggleable(),
                ToggleColumn::make('is_active')->label('Activo'),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('results')
                        ->label('Resultados')
                        ->icon(Heroicon::OutlinedChartBar)
                        ->url(fn (Announcement $record): string => static::getUrl('results', ['record' => $record])),
                    Action::make('previewOnSite')
                        ->label('Probar en el portal')
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->tooltip('Abre la portada con esta ventana, aunque esté desactivada o programada. Solo usted la ve.')
                        ->url(fn (Announcement $record): string => route('home', [SiteAnnouncements::PREVIEW_PARAMETER => $record->id]))
                        ->openUrlInNewTab(),
                    ReplicateAction::make()
                        ->label('Duplicar')
                        ->excludeAttributes(['is_active'])
                        ->beforeReplicaSaved(fn (Announcement $replica) => $replica->fill(['title' => Str::limit($replica->title, 140).' (copia)', 'is_active' => false]))
                        ->successRedirectUrl(fn (Announcement $replica): string => static::getUrl('edit', ['record' => $replica]))
                        ->successNotificationTitle('Anuncio duplicado como desactivado. Ajústelo y actívelo cuando esté listo.'),
                    DeleteAction::make(),
                ])->tooltip('Más acciones'),
            ]);
    }

    /**
     * Registered here so their lazy loading and filters work (Livewire must know them by name).
     */
    public static function getWidgets(): array
    {
        return [
            AnnouncementStatsOverview::class,
            AnnouncementActivityChart::class,
            AnnouncementDailyStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
            'results' => AnnouncementResults::route('/{record}/resultados'),
        ];
    }
}
