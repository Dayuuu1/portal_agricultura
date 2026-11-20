<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Concerns\ControlsPublishing;
use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Document;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group as LayoutGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class PageResource extends Resource
{
    use ControlsPublishing, HasSpanishLabels;

    protected static ?string $model = Page::class;

    protected static ?string $slug = 'paginas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'página';

    protected static ?string $pluralModelLabel = 'páginas';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Contenido')
                    ->description('Lo que verá la ciudadanía en la página.')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->columnSpan(['default' => 3, 'lg' => 2])
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        Textarea::make('excerpt')
                            ->label('Resumen')
                            ->helperText('Una o dos líneas bajo el título; también la muestran los buscadores.')
                            ->rows(2)
                            ->maxLength(255),
                        RichEditor::make('body')
                            ->label('Texto de la página')
                            ->helperText('Use «Título 2» para cada sección (por ejemplo «Principios», «Deberes», «Prohibiciones»): con dos o más secciones, la página muestra un índice lateral.')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('paginas')
                            ->fileAttachmentsVisibility('public'),
                    ]),
                LayoutGroup::make([
                    Section::make('Publicación')
                        ->icon(Heroicon::OutlinedGlobeAlt)
                        ->schema([
                            static::publishedToggle('Publicada', 'Si la desmarca, la página deja de verse en el portal.'),
                            Toggle::make('show_in_menu')
                                ->label('Mostrar en el menú «Institucional»')
                                ->helperText('Aparece junto a Nosotros, Organigrama y Directorio.'),
                            TextInput::make('slug')
                                ->label('Dirección de la página')
                                ->prefix('/p/')
                                ->maxLength(255)
                                ->alphaDash()
                                ->unique(ignoreRecord: true)
                                ->disabled(fn (?Page $record): bool => $record?->isSystemPage() ?? false)
                                ->helperText(fn (?Page $record): string => $record?->isSystemPage()
                                    ? 'Página del sistema: su dirección no puede modificarse.'
                                    : 'Se genera a partir del título.'),
                        ]),
                    Section::make('Documentos para descargar')
                        ->description('Elija documentos ya publicados en «Documentos», por ejemplo el texto aprobado o la resolución que lo aprueba.')
                        ->icon(Heroicon::OutlinedPaperClip)
                        ->schema([
                            Select::make('documents')
                                ->hiddenLabel()
                                ->relationship('documents', 'title', fn (Builder $query): Builder => $query->where('is_published', true))
                                ->getOptionLabelFromRecordUsing(fn (Document $record): string => collect([$record->title, $record->period_year])->filter()->implode(' · '))
                                ->multiple()
                                ->searchable()
                                ->placeholder('Buscar por título…'),
                        ]),
                ])->columnSpan(['default' => 3, 'lg' => 1]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Título')->searchable()->weight('semibold')
                    ->description(fn (Page $record): ?string => $record->excerpt),
                TextColumn::make('slug')->label('URL')->prefix('/p/')->color('gray'),
                IconColumn::make('is_published')->label('Publicada')->boolean(),
                IconColumn::make('show_in_menu')->label('En el menú')->boolean()->alignCenter(),
                TextColumn::make('documents_count')->label('Documentos')->counts('documents')->alignCenter(),
                TextColumn::make('updated_at')->label('Actualizada')->since()->sortable(),
            ])
            ->recordActions([
                Action::make('viewOnSite')
                    ->label('Ver')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Page $record): string => route('pages.show', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Page $record): bool => $record->isSystemPage()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
