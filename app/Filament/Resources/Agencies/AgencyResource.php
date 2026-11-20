<?php

namespace App\Filament\Resources\Agencies;

use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Agencies\Pages\CreateAgency;
use App\Filament\Resources\Agencies\Pages\EditAgency;
use App\Filament\Resources\Agencies\Pages\ListAgencies;
use App\Models\Agency;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

class AgencyResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = Agency::class;

    protected static ?string $slug = 'agencias-agrarias';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Institucional';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'agencia agraria';

    protected static ?string $pluralModelLabel = 'agencias agrarias';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Agencia')
                    ->description('Ubicación y servicios que se muestran en la ficha pública de la agencia.')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
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
                            ->unique(ignoreRecord: true),
                        Select::make('province')
                            ->label('Provincia')
                            ->options(['Tambopata' => 'Tambopata', 'Manu' => 'Manu', 'Tahuamanu' => 'Tahuamanu'])
                            ->required(),
                        TextInput::make('district')
                            ->label('Distrito')
                            ->maxLength(255),
                        TextInput::make('address')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        RichEditor::make('description')
                            ->label('Presentación')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'link', 'undo', 'redo'])
                            ->columnSpanFull(),
                        TagsInput::make('services')
                            ->label('Servicios')
                            ->placeholder('Escriba un servicio y presione Enter')
                            ->columnSpanFull(),
                        TextInput::make('latitude')
                            ->label('Latitud')
                            ->placeholder('-12.5933')
                            ->helperText('Opcional. Cópiela de Google Maps (clic derecho sobre el lugar).')
                            ->numeric()
                            ->minValue(-90)
                            ->maxValue(90),
                        TextInput::make('longitude')
                            ->label('Longitud')
                            ->numeric()
                            ->minValue(-180)
                            ->maxValue(180),
                    ]),
                Section::make('Contacto')
                    ->description('Datos de atención al público.')
                    ->icon(Heroicon::OutlinedPhone)
                    ->columnSpan(1)
                    ->schema([
                        FileUpload::make('photo')
                            ->label('Foto de la sede')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk('public')
                            ->directory('agencias')
                            ->maxSize(4096),
                        TextInput::make('head_name')->label('Responsable')->maxLength(255),
                        TextInput::make('phone')->label('Teléfono')->maxLength(50),
                        TextInput::make('email')->label('Correo')->email()->maxLength(255),
                        TextInput::make('schedule')->label('Horario')->maxLength(255),
                        TextInput::make('sort_order')->label('Orden')->numeric()->default(0),
                        Toggle::make('is_active')->label('Visible en el portal')->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->emptyStateIcon(Heroicon::OutlinedMapPin)
            ->emptyStateHeading('Aún no hay agencias registradas')
            ->emptyStateDescription('Agregue las agencias agrarias que atienden en cada provincia.')
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->weight('semibold')
                    ->description(fn (Agency $record): ?string => collect([$record->district, $record->address])->filter()->implode(' · ') ?: null),
                TextColumn::make('province')->label('Provincia')->badge(),
                TextColumn::make('phone')->label('Teléfono')->placeholder('—'),
                TextColumn::make('head_name')->label('Responsable')->placeholder('—'),
                IconColumn::make('is_active')->label('Visible')->boolean(),
            ])
            ->recordActions([
                Action::make('viewOnSite')
                    ->label('Ver')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Agency $record): string => route('agencies.show', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgencies::route('/'),
            'create' => CreateAgency::route('/create'),
            'edit' => EditAgency::route('/{record}/edit'),
        ];
    }
}
