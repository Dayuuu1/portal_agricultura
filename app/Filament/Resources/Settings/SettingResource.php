<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Concerns\HasSpanishLabels;
use App\Filament\Resources\Settings\Pages\ManageSettings;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SettingResource extends Resource
{
    use HasSpanishLabels;

    protected static ?string $model = Setting::class;

    protected static ?string $slug = 'datos-del-portal';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Datos del portal';

    protected static ?string $modelLabel = 'dato del portal';

    protected static ?string $pluralModelLabel = 'datos del portal';

    protected static ?string $recordTitleAttribute = 'label';

    private const GROUPS = [
        'general' => 'Identidad de la entidad',
        'institucional' => 'Misión y visión',
        'contacto' => 'Contacto y ubicación',
        'redes' => 'Redes sociales',
        'servicios' => 'Enlaces a servicios',
    ];

    /**
     * Where each value appears, shown to editors as guidance.
     */
    private const HINTS = [
        'site_name' => 'Título del navegador, pie de página y resultados de Google.',
        'site_acronym' => 'Etiqueta destacada en la portada.',
        'parent_entity' => 'Barra superior del portal.',
        'site_description' => 'Pie de página y descripción para buscadores.',
        'mission' => 'Página «Nosotros». Reemplace el texto referencial por el oficial.',
        'vision' => 'Página «Nosotros». Reemplace el texto referencial por el oficial.',
        'address' => 'Pie de página, portada y Contacto.',
        'phone_main' => 'Barra superior, pie de página y Contacto.',
        'phone_director' => 'Pie de página y Contacto.',
        'phone_procurement' => 'Pie de página y Contacto.',
        'email' => 'Barra superior, pie de página y Contacto.',
        'schedule' => 'Pie de página, portada y Contacto.',
        'map_embed_url' => 'En Google Maps: Compartir → Insertar un mapa → copie solo la dirección que está dentro de src="…".',
        'facebook_url' => 'Ícono en la cabecera y el pie. Déjelo vacío para ocultarlo.',
        'youtube_url' => 'Ícono en la cabecera y el pie. Déjelo vacío para ocultarlo.',
        'mesa_partes_url' => 'Si lo completa, aparece un acceso directo en la portada y en Contacto.',
        'libro_reclamaciones_url' => 'Opcional. Si la entidad está registrada en la plataforma digital del Estado (GOB.PE), pegue aquí su enlace y se mostrará junto al formulario del portal.',
        'complaints_email' => 'Cada reclamo registrado en el portal llega a este correo con sus adjuntos. También se guarda en «Libro de reclamaciones» del panel.',
        'transparencia_url' => 'Página de la entidad en el Portal de Transparencia Estándar.',
        'sigaweb_url' => 'Sistema interno; no se muestra al público.',
    ];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label('Dato')
                    ->disabled()
                    ->columnSpanFull(),
                Textarea::make('value')
                    ->label('Valor')
                    ->helperText(fn (?Setting $record): ?string => self::HINTS[$record?->key] ?? null)
                    ->rows(5)
                    ->columnSpanFull()
                    ->visible(fn (?Setting $record): bool => $record?->type === 'textarea'),
                TextInput::make('value')
                    ->label('Valor')
                    ->helperText(fn (?Setting $record): ?string => self::HINTS[$record?->key] ?? null)
                    ->placeholder(fn (?Setting $record): ?string => $record?->type === 'url' ? 'https://…' : null)
                    ->maxLength(500)
                    ->columnSpanFull()
                    ->url(fn (?Setting $record): bool => $record?->type === 'url')
                    ->email(fn (?Setting $record): bool => $record?->type === 'email')
                    ->visible(fn (?Setting $record): bool => $record?->type !== 'textarea'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->defaultGroup(
                Group::make('group')
                    ->label('Grupo')
                    ->getTitleFromRecordUsing(fn (Setting $record): string => self::GROUPS[$record->group] ?? ucfirst($record->group))
                    ->orderQueryUsing(function (Builder $query): Builder {
                        $column = $query->getQuery()->getGrammar()->wrap('group');
                        $cases = collect(array_keys(self::GROUPS))->map(fn (string $key, int $index): string => "WHEN '{$key}' THEN {$index}")->implode(' ');

                        return $query->orderByRaw("CASE {$column} {$cases} ELSE 99 END");
                    })
                    ->collapsible(),
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('label')
                    ->label('Dato')
                    ->weight('semibold')
                    ->description(fn (Setting $record): ?string => self::HINTS[$record->key] ?? null)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('value')
                    ->label('Valor actual')
                    ->limit(70)
                    ->placeholder('Sin configurar')
                    ->color(fn (?string $state): ?string => blank($state) ? 'warning' : null)
                    ->wrap(),
            ])
            ->recordActions([
                EditAction::make()->label('Cambiar'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSettings::route('/'),
        ];
    }
}
