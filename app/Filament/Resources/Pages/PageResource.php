<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Concerns\ControlsPublishing;
use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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
            ->components([
                Section::make()
                    ->columns(2)
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
                        TextInput::make('slug')
                            ->label('URL amigable')
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (?Page $record): bool => $record?->isSystemPage() ?? false)
                            ->helperText(fn (?Page $record): ?string => $record?->isSystemPage() ? 'Página del sistema: su URL no puede modificarse.' : null),
                        TextInput::make('excerpt')
                            ->label('Bajada / resumen')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        RichEditor::make('body')
                            ->label('Contenido')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('paginas')
                            ->fileAttachmentsVisibility('public')
                            ->columnSpanFull(),
                        static::publishedToggle('Publicada'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Título')->searchable(),
                TextColumn::make('slug')->label('URL')->prefix('/p/')->color('gray'),
                IconColumn::make('is_published')->label('Publicada')->boolean(),
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
