<?php

namespace App\Filament\Resources\Slides;

use App\Enums\BannerOverlay;
use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Slides\Pages\ManageSlides;
use App\Models\Setting;
use App\Models\Slide;
use App\Rules\SafeLink;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;
use UnitEnum;

class SlideResource extends Resource
{
    use HasSpanishLabels;

    public const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    protected static ?string $model = Slide::class;

    protected static ?string $slug = 'banners';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Banners de portada';

    protected static ?string $modelLabel = 'banner';

    protected static ?string $pluralModelLabel = 'banners';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Vista previa')
                    ->description('Así se verá en la portada (pantalla de computadora). Se actualiza al escribir o subir imágenes.')
                    ->icon(Heroicon::OutlinedEye)
                    ->collapsible()
                    ->schema([
                        View::make('filament.slides.preview')->viewData(fn (Get $get): array => [
                            'eyebrow' => $get('eyebrow'),
                            'title' => $get('title'),
                            'subtitle' => $get('subtitle'),
                            'buttonText' => $get('button_text'),
                            'hasButton' => filled($get('button_url')),
                            'background' => static::previewUrl($get('background_image')),
                            'image' => static::previewUrl($get('image')),
                            'overlay' => $get('overlay') instanceof BannerOverlay ? $get('overlay') : (BannerOverlay::tryFrom((string) $get('overlay')) ?? BannerOverlay::Medium),
                        ]),
                    ]),
                Grid::make(['default' => 1, 'lg' => 5])->schema([
                    Section::make('Texto')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->columnSpan(['lg' => 3])
                        ->columns(2)
                        ->schema([
                            TextInput::make('eyebrow')
                                ->label('Etiqueta superior')
                                ->placeholder(fn (): string => (Setting::valueOf('site_acronym') ?? 'DRDAR').' · Madre de Dios')
                                ->helperText('Texto pequeño sobre el título. Si lo deja vacío se usa la sigla de la entidad.')
                                ->maxLength(60)
                                ->live(onBlur: true)
                                ->columnSpanFull(),
                            TextInput::make('title')
                                ->label('Título')
                                ->helperText('Corto y directo: idealmente menos de 60 caracteres.')
                                ->required()
                                ->maxLength(120)
                                ->live(onBlur: true)
                                ->columnSpanFull(),
                            Textarea::make('subtitle')
                                ->label('Texto')
                                ->rows(2)
                                ->maxLength(300)
                                ->live(onBlur: true)
                                ->columnSpanFull(),
                            TextInput::make('button_text')
                                ->label('Texto del botón')
                                ->placeholder('Ver más')
                                ->maxLength(40)
                                ->live(onBlur: true),
                            TextInput::make('button_url')
                                ->label('Enlace del botón')
                                ->placeholder('/convocatorias')
                                ->helperText('Ruta interna (/convocatorias) o dirección completa (https://…). Sin enlace no se muestra el botón.')
                                ->maxLength(255)
                                ->rule(new SafeLink)
                                ->live(onBlur: true),
                        ]),
                    Section::make('Publicación')
                        ->icon(Heroicon::OutlinedCalendarDays)
                        ->columnSpan(['lg' => 2])
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Activo')
                                ->helperText('Desactívelo para ocultarlo sin borrarlo.')
                                ->default(true),
                            DateTimePicker::make('starts_at')
                                ->label('Mostrar desde')
                                ->helperText('Opcional. Útil para anunciar una convocatoria o campaña con anticipación.')
                                ->seconds(false),
                            DateTimePicker::make('ends_at')
                                ->label('Mostrar hasta')
                                ->helperText('Opcional. Pasada esta fecha el banner se oculta solo.')
                                ->seconds(false)
                                ->after('starts_at'),
                            TextInput::make('sort_order')
                                ->label('Orden')
                                ->numeric()
                                ->default(fn (): int => (int) Slide::query()->max('sort_order') + 1),
                        ]),
                ]),
                Section::make('Imágenes')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        Grid::make(1)->schema([
                            FileUpload::make('background_image')
                                ->label('Imagen de fondo')
                                ->helperText('Foto horizontal que cubre todo el banner (paisajes, cultivos, productores). Recomendado: 1920 × 800 px, JPG o WEBP, máximo 5 MB. Se oscurece para que el texto se lea.')
                                ->image()
                                ->acceptedFileTypes(self::IMAGE_TYPES)
                                ->imageEditor()
                                ->imageEditorAspectRatios(['21:9', '16:9', '12:5'])
                                ->imageResizeMode('contain')
                                ->imageResizeTargetWidth('1920')
                                ->imageResizeUpscale(false)
                                ->disk('public')
                                ->directory('banners/fondos')
                                ->maxSize(5120)
                                ->live(),
                            ToggleButtons::make('overlay')
                                ->label('Oscurecer la imagen de fondo')
                                ->options(BannerOverlay::class)
                                ->default(BannerOverlay::Medium)
                                ->inline()
                                ->live()
                                ->helperText('Media funciona con la mayoría de fotos. Use Fuerte si el texto no se lee bien en la vista previa.'),
                        ]),
                        FileUpload::make('image')
                            ->label('Imagen lateral (opcional)')
                            ->helperText('Se muestra a la derecha, en recuadro, en pantallas grandes. Recomendado: 1200 × 900 px (4:3). Si no sube una, se muestran las cifras del portal.')
                            ->image()
                            ->acceptedFileTypes(self::IMAGE_TYPES)
                            ->imageEditor()
                            ->imageEditorAspectRatios(['4:3'])
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth('1200')
                            ->imageResizeUpscale(false)
                            ->disk('public')
                            ->directory('banners')
                            ->maxSize(4096)
                            ->live(),
                    ]),
            ]);
    }

    /**
     * URL for the preview: either the stored file or the file just uploaded (not saved yet).
     */
    public static function previewUrl(mixed $state): ?string
    {
        $file = is_array($state) ? collect($state)->first() : $state;

        try {
            return match (true) {
                $file instanceof TemporaryUploadedFile => $file->temporaryUrl(),
                is_string($file) && $file !== '' => Storage::disk('public')->url($file),
                default => null,
            };
        } catch (Throwable) {
            return null;
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->emptyStateHeading('Aún no hay banners')
            ->emptyStateDescription('Cree el primero con «Nuevo banner»: un título, una foto de fondo y un botón hacia la sección que quiera destacar.')
            ->columns([
                ImageColumn::make('background_image')
                    ->label('Fondo')
                    ->disk('public')
                    ->imageWidth(150)
                    ->imageHeight(64)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                    ->placeholder('Sin fondo'),
                TextColumn::make('title')
                    ->label('Banner')
                    ->searchable()
                    ->weight('semibold')
                    ->wrap()
                    ->description(fn (Slide $record): ?string => $record->subtitle),
                ImageColumn::make('image')
                    ->label('Imagen lateral')
                    ->disk('public')
                    ->imageHeight(48)
                    ->extraImgAttributes(['class' => 'rounded-md'])
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('visibility')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Slide $record): string => $record->visibility()['label'])
                    ->color(fn (Slide $record): string => $record->visibility()['color'])
                    ->description(fn (Slide $record): ?string => match (true) {
                        $record->starts_at && $record->ends_at => $record->starts_at->format('d/m/Y').' – '.$record->ends_at->format('d/m/Y'),
                        (bool) $record->starts_at => 'Desde '.$record->starts_at->format('d/m/Y H:i'),
                        (bool) $record->ends_at => 'Hasta '.$record->ends_at->format('d/m/Y H:i'),
                        default => null,
                    }),
                TextColumn::make('button_url')->label('Botón')->color('gray')->placeholder('Sin botón')
                    ->description(fn (Slide $record): ?string => $record->button_url ? ($record->button_text ?: 'Ver más') : null),
                ToggleColumn::make('is_active')->label('Activo'),
            ])
            ->recordActions([
                EditAction::make()->modalWidth(Width::SevenExtraLarge),
                ReplicateAction::make()
                    ->label('Duplicar')
                    ->excludeAttributes(['is_active'])
                    ->beforeReplicaSaved(fn (Slide $replica) => $replica->fill(['title' => $replica->title.' (copia)', 'is_active' => false]))
                    ->successNotificationTitle('Banner duplicado como desactivado. Edítelo y actívelo cuando esté listo.'),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSlides::route('/'),
        ];
    }
}
