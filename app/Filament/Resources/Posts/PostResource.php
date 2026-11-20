<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Concerns\ControlsPublishing;
use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Post;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use UnitEnum;

class PostResource extends Resource
{
    use ControlsPublishing, HasSpanishLabels;

    protected static ?string $model = Post::class;

    protected static ?string $slug = 'noticias';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'noticia';

    protected static ?string $pluralModelLabel = 'noticias';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Contenido')
                    ->description('Lo que leerá la ciudadanía. Escriba en tono informativo y evite títulos en mayúsculas.')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->placeholder('Ej.: DRA inició campaña de vacunación de ganado en Tambopata')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('URL amigable')
                            ->helperText('Se genera desde el título. Solo letras minúsculas, números y guiones.')
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        Textarea::make('excerpt')
                            ->label('Resumen')
                            ->helperText('Una o dos oraciones. Se muestra en los listados y al compartir en redes sociales; si lo deja vacío se toma el inicio del contenido.')
                            ->rows(3)
                            ->maxLength(500),
                        RichEditor::make('body')
                            ->label('Contenido')
                            ->helperText('Puede insertar enlaces, listas e imágenes. Para documentos, súbalos en «Documentos» y enlácelos aquí.')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('noticias/adjuntos')
                            ->fileAttachmentsVisibility('public'),
                        FileUpload::make('gallery')
                            ->label('Galería de fotos')
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk('public')
                            ->directory('noticias/galeria')
                            ->maxFiles(20)
                            ->maxSize(4096)
                            ->panelLayout('grid')
                            ->helperText('Hasta 20 fotos de 4 MB. Arrástrelas para cambiar el orden.'),
                    ]),
                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Publicación')
                            ->description('Cuándo y cómo se muestra.')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->schema([
                                static::publishedToggle('Publicada', 'Desmárquela para guardarla como borrador.'),
                                DateTimePicker::make('published_at')
                                    ->label('Fecha de publicación')
                                    ->helperText('Si elige una fecha futura, la noticia aparecerá sola en esa fecha.')
                                    ->default(now())
                                    ->seconds(false)
                                    ->required(),
                                Toggle::make('is_featured')
                                    ->label('Destacada en portada')
                                    ->helperText('Aparece en grande en la página de inicio.'),
                                Select::make('category_id')
                                    ->label('Categoría')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                                    ]),
                            ]),
                        Section::make('Imagen de portada')
                            ->description('Se usa en la portada, el listado y al compartir.')
                            ->icon(Heroicon::OutlinedPhoto)
                            ->schema([
                                FileUpload::make('cover_image')
                                    ->hiddenLabel()
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->imageEditor()
                                    ->disk('public')
                                    ->directory('noticias')
                                    ->maxSize(4096)
                                    ->helperText('Recomendado: 1600 × 900 px, JPG o WEBP.'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['category', 'author']))
            ->striped()
            ->emptyStateIcon(Heroicon::OutlinedNewspaper)
            ->emptyStateHeading('No hay noticias en esta vista')
            ->emptyStateDescription('Use «Publicar noticia» para crear la primera o cambie de pestaña.')
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('')
                    ->disk('public')
                    ->imageSize(56)
                    ->square()
                    ->defaultImageUrl(asset('images/logo-emblema.png')),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->weight('semibold')
                    ->limit(80)
                    ->tooltip(fn (Post $record): string => $record->title)
                    ->description(fn (Post $record): string => collect([$record->category?->name, $record->author ? 'por '.$record->author->name : null])->filter()->implode(' · '))
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Post $record): string => match (true) {
                        ! $record->is_published => 'Borrador',
                        $record->published_at?->isFuture() => 'Programada',
                        default => 'Publicada',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Publicada' => 'success',
                        'Programada' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): Heroicon => match ($state) {
                        'Publicada' => Heroicon::OutlinedCheckCircle,
                        'Programada' => Heroicon::OutlinedClock,
                        default => Heroicon::OutlinedPencil,
                    }),
                IconColumn::make('is_featured')
                    ->label('Destacada')
                    ->boolean()
                    ->trueIcon(Heroicon::Star)
                    ->falseIcon(Heroicon::OutlinedStar)
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->alignCenter(),
                TextColumn::make('published_at')
                    ->label('Publicación')
                    ->date('d/m/Y')
                    ->description(fn (Post $record): ?string => $record->published_at?->diffForHumans())
                    ->sortable(),
                TextColumn::make('views')
                    ->label('Visitas')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoría')
                    ->relationship('category', 'name'),
                TernaryFilter::make('is_published')->label('Publicada'),
                TernaryFilter::make('is_featured')->label('Destacada'),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('viewOnSite')
                        ->label('Ver en el portal')
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->url(fn (Post $record): string => route('posts.show', $record))
                        ->openUrlInNewTab()
                        ->visible(fn (Post $record): bool => $record->is_published && (bool) $record->published_at?->isPast()),
                    DeleteAction::make(),
                ])->tooltip('Más acciones'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Publicar')
                        ->icon(Heroicon::OutlinedEye)
                        ->visible(fn (): bool => static::canPublish())
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')
                        ->label('Pasar a borrador')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->visible(fn (): bool => static::canPublish())
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()->authorizeIndividualRecords(),
                ]),
            ]);
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Categoría' => $record->category?->name ?? '—',
            'Fecha' => $record->published_at?->format('d/m/Y') ?? '—',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
