<?php

namespace App\Filament\Resources\DocumentTypes;

use App\Enums\DocumentSection;
use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\DocumentTypes\Pages\ManageDocumentTypes;
use App\Models\DocumentType;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DocumentTypeResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = DocumentType::class;

    protected static ?string $slug = 'tipos-de-documento';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Documentos';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'tipo de documento';

    protected static ?string $pluralModelLabel = 'tipos de documento';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                Select::make('section')->label('Sección del portal')->options(DocumentSection::class)->required(),
                TextInput::make('slug')->label('URL amigable')->alphaDash()->maxLength(255)->unique(ignoreRecord: true)
                    ->helperText('Déjelo vacío para generarlo automáticamente.'),
                TextInput::make('sort_order')->label('Orden')->numeric()->default(0),
                TextInput::make('description')->label('Descripción')->maxLength(255)->columnSpanFull()
                    ->helperText('Se muestra en el portal sobre el listado de este tipo.'),
                Toggle::make('group_by_year')
                    ->label('Organizar por años en el portal')
                    ->helperText('Recomendado para publicaciones periódicas (estadística, boletines, resoluciones): el portal abre en el año más reciente y muestra botones por año.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->defaultGroup(Group::make('section')->label('Sección del portal')->titlePrefixedWithLabel(false))
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withMin('documents as first_year', 'period_year')
                ->withMax('documents as last_year', 'period_year'))
            ->columns([
                TextColumn::make('name')
                    ->label('Tipo')
                    ->weight('semibold')
                    ->description(fn (DocumentType $record): ?string => $record->description)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('documents_count')
                    ->label('Documentos')
                    ->counts('documents')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),
                TextColumn::make('years')
                    ->label('Años')
                    ->state(fn (DocumentType $record): string => match (true) {
                        $record->first_year === null => '—',
                        $record->first_year === $record->last_year => (string) $record->first_year,
                        default => "{$record->first_year}–{$record->last_year}",
                    }),
                ToggleColumn::make('group_by_year')
                    ->label('Por años')
                    ->tooltip('Organizar por años en el portal'),
            ])
            ->recordActions([
                Action::make('upload')
                    ->label('Subir')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->url(fn (DocumentType $record): string => DocumentResource::getUrl('create', ['tipo' => $record->slug])),
                ActionGroup::make([
                    Action::make('documents')
                        ->label('Ver sus documentos')
                        ->icon(Heroicon::OutlinedFolderOpen)
                        ->url(fn (DocumentType $record): string => DocumentResource::getUrl(parameters: ['filters' => ['documentType' => ['value' => $record->id]]])),
                    Action::make('viewOnSite')
                        ->label('Ver en el portal')
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->url(fn (DocumentType $record): string => route('documents.index', ['tipo' => $record->slug]))
                        ->openUrlInNewTab(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->hidden(fn (DocumentType $record): bool => $record->documents()->exists()),
                ])->tooltip('Más acciones'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDocumentTypes::route('/'),
        ];
    }
}
