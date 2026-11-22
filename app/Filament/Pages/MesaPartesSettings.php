<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\User;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Everything of the Mesa de Partes Virtual that the entity's rules may change (schedule, terms, file limits,
 * texts), so a new directive or law does not need a code change. Stored in «settings» (group mesa_partes).
 */
class MesaPartesSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Mesa de partes';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Configuración';

    protected static ?string $title = 'Configuración de la mesa de partes';

    protected static ?string $slug = 'mesa-de-partes/configuracion';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    private const BOOLEANS = ['mpv_enabled', 'mpv_allow_legal_entities', 'mpv_public_lookup'];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasPermission('mesa_partes_config.view');
    }

    public function canEdit(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasPermission('mesa_partes_config.update');
    }

    public function getSubheading(): string
    {
        return 'Ajuste el servicio a la directiva de la entidad. Los cambios se aplican a los documentos que se presenten desde ahora.';
    }

    public function mount(): void
    {
        $this->form->fill($this->storedValues());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Recepción')
                    ->icon(Heroicon::OutlinedInboxArrowDown)
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Toggle::make('mpv_enabled')->label('Recibir documentos')->columnSpanFull()
                            ->helperText('Actívelo cuando la entidad apruebe el servicio. Apagado: no se crean cuentas ni se reciben documentos, pero quienes ya tienen cuenta pueden ver sus trámites.'),
                        TextInput::make('mpv_opening_time')->label('Inicio de la atención')->type('time')->required()
                            ->rule('date_format:H:i'),
                        TextInput::make('mpv_closing_time')->label('Hora de cierre')->type('time')->required()
                            ->rule('date_format:H:i')
                            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                if ((string) $value <= (string) $get('mpv_opening_time')) {
                                    $fail('La hora de cierre debe ser posterior al inicio de la atención.');
                                }
                            })
                            ->helperText('Lo que llegue después, o en día no hábil, se considera presentado el día hábil siguiente (Ley N° 31170).'),
                        TextInput::make('mpv_approval')->label('Norma que aprueba el servicio')->maxLength(255)
                            ->placeholder('Ej.: R.D. N° 045-2026-GOREMAD/DRDAR'),
                    ]),
                Section::make('Plazos')
                    ->icon(Heroicon::OutlinedClock)
                    ->description('En días hábiles. Los feriados, la Semana Santa y los días no laborables registrados no se cuentan.')
                    ->schema([
                        TextInput::make('mpv_cure_days')->label('Para subsanar observaciones')->numeric()->integer()->minValue(1)->maxValue(15)->required()->suffix('días')
                            ->helperText('El TUO de la Ley N° 27444 (art. 136) fija 2 días hábiles.'),
                        TextInput::make('mpv_response_days')->label('Plazo de atención por defecto')->numeric()->integer()->minValue(1)->maxValue(365)->required()->suffix('días')
                            ->helperText('Para los tipos de trámite que no tienen un plazo propio.'),
                    ]),
                Section::make('Archivos')
                    ->icon(Heroicon::OutlinedPaperClip)
                    ->description('El documento principal debe ser PDF; los anexos pueden ser PDF, JPG o PNG.')
                    ->schema([
                        TextInput::make('mpv_max_file_mb')->label('Tamaño máximo por archivo')->numeric()->integer()->minValue(1)->maxValue(50)->required()->suffix('MB'),
                        TextInput::make('mpv_max_total_mb')->label('Tamaño máximo por trámite')->numeric()->integer()->minValue(1)->maxValue(200)->required()->suffix('MB'),
                        TextInput::make('mpv_max_attachments')->label('Cantidad máxima de anexos')->numeric()->integer()->minValue(0)->maxValue(30)->required(),
                    ]),
                Section::make('Cuentas, avisos y numeración')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Toggle::make('mpv_allow_legal_entities')->label('Permitir cuentas de personas jurídicas')
                            ->helperText('Empresas y organizaciones, con RUC y representante legal.'),
                        Toggle::make('mpv_public_lookup')->label('Consulta de expedientes sin cuenta')
                            ->helperText('Con el número del cargo y el documento de quien presentó. Muestra solo el estado y la hoja de ruta.'),
                        TextInput::make('mpv_staff_email')->label('Correo que recibe los avisos')->email()->maxLength(150)
                            ->placeholder(fn (): string => Setting::valueOf('email') ?? 'correo@entidad.gob.pe')
                            ->helperText('Avisa cada documento nuevo y cada subsanación. Vacío: el correo institucional de «Datos del portal».'),
                        TextInput::make('mpv_code_prefix')->label('Prefijo del número de expediente')->required()->maxLength(10)->alphaNum()
                            ->helperText(fn (Get $get): string => 'Ej.: '.strtoupper((string) ($get('mpv_code_prefix') ?: 'MPV')).'-'.now()->year.'-000001. Cambiarlo no altera los números ya asignados.'),
                    ]),
                Section::make('Textos para la ciudadanía')
                    ->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Textarea::make('mpv_instructions')->label('Indicaciones adicionales')->rows(5)->maxLength(3000)
                            ->helperText('Opcional. Aparecen al inicio de la página de la mesa de partes.'),
                        Textarea::make('mpv_legal_basis')->label('Base legal')->rows(5)->maxLength(3000)
                            ->helperText('Una norma por línea. Se muestra en la página pública.'),
                    ]),
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
                        Action::make('save')->label('Guardar configuración')->submit('save')->keyBindings(['mod+s']),
                    ])->sticky()->visible($this->canEdit()),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publicPage')
                ->label('Ver página pública')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(route('mesa-partes.index'))
                ->openUrlInNewTab(),
        ];
    }

    public function save(): void
    {
        abort_unless($this->canEdit(), 403);

        $state = $this->form->getState();
        $changed = 0;

        foreach (Setting::query()->where('group', 'mesa_partes')->get() as $setting) {
            if (! array_key_exists($setting->key, $state)) {
                continue;
            }

            $value = $state[$setting->key];
            $value = match (true) {
                in_array($setting->key, self::BOOLEANS, true) => $value ? '1' : '0',
                $setting->key === 'mpv_code_prefix' => strtoupper(trim((string) $value)),
                is_string($value) => trim($value) === '' ? null : trim($value),
                $value === null => null,
                default => (string) $value,
            };

            if ($value !== $setting->value) {
                $setting->update(['value' => $value]);
                $changed++;
            }
        }

        $this->form->fill($this->storedValues());

        Notification::make()
            ->title($changed > 0 ? 'Configuración guardada' : 'No había cambios por guardar')
            ->success()
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    private function storedValues(): array
    {
        return Setting::query()->where('group', 'mesa_partes')->pluck('value', 'key')
            ->map(fn (?string $value, string $key): mixed => in_array($key, self::BOOLEANS, true) ? $value === '1' : $value)
            ->all();
    }
}
