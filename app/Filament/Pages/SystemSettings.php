<?php

namespace App\Filament\Pages;

use App\Mail\OutgoingMailTest;
use App\Models\Setting;
use App\Models\User;
use App\Support\OutgoingMail;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Once;
use Illuminate\Support\Str;
use Throwable;
use UnitEnum;

/**
 * Outgoing mail server and two-step verification policy. Both can lock the entity out of its own mail or panel
 * if set wrong, so only administrators see this page. Stored in «settings» (group sistema); the SMTP password
 * is encrypted with the APP_KEY and never sent back to the browser.
 */
class SystemSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Correo y acceso';

    protected static ?string $title = 'Correo y acceso al panel';

    protected static ?string $slug = 'correo-y-acceso';

    public const MFA_POLICIES = [
        'none' => 'Opcional',
        'admins' => 'Obligatoria para administradores',
        'all' => 'Obligatoria para todo el personal',
    ];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->is_active && $user->is_admin;
    }

    public function getSubheading(): string
    {
        return 'Solo los administradores ven esta página.';
    }

    public function mount(): void
    {
        $this->form->fill($this->storedValues());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Correo saliente')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->description('Servidor con el que el portal envía las constancias del libro de reclamaciones, los cargos y avisos de la mesa de partes y los enlaces para restablecer contraseñas.')
                    ->columns(2)
                    ->schema([
                        Callout::make(fn (): string => match (OutgoingMail::status()['state']) {
                            'database' => 'Se usa el servidor configurado aquí',
                            'env' => 'Se usa el servidor del archivo .env',
                            default => 'Los correos no se están enviando',
                        })
                            ->description(fn (): string => OutgoingMail::status()['description'])
                            ->status(fn (): string => match (OutgoingMail::status()['state']) {
                                'database' => 'success',
                                'env' => 'info',
                                default => 'danger',
                            })
                            ->columnSpanFull(),
                        Toggle::make('mail_enabled')
                            ->label('Enviar con este servidor')
                            ->helperText('Apagado: se usa la configuración del archivo .env del servidor. Pruebe el envío antes de activarlo.')
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('mail_host')
                            ->label('Servidor SMTP')
                            ->placeholder('smtp.office365.com')
                            ->maxLength(255)
                            ->regex('/^[A-Za-z0-9.-]+$/')
                            ->validationMessages(['regex' => 'Escriba solo el nombre o la IP del servidor, sin «smtp://» ni espacios.'])
                            ->required(fn (Get $get): bool => (bool) $get('mail_enabled')),
                        Select::make('mail_encryption')
                            ->label('Cifrado')
                            ->options(OutgoingMail::ENCRYPTIONS)
                            ->required()
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(function (?string $state, ?string $old, Get $get, Set $set): void {
                                // Follow the usual port of the new mode unless a custom one was typed.
                                $previousDefault = OutgoingMail::DEFAULT_PORTS[$old] ?? null;

                                if (blank($get('mail_port')) || (int) $get('mail_port') === $previousDefault) {
                                    $set('mail_port', (string) (OutgoingMail::DEFAULT_PORTS[$state] ?? 587));
                                }
                            })
                            ->helperText('STARTTLS en el puerto 587 o SSL/TLS en el 465. Sin cifrado solo para un servidor de la red interna.'),
                        TextInput::make('mail_port')
                            ->label('Puerto')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(65535)
                            ->required(),
                        TextInput::make('mail_username')
                            ->label('Usuario')
                            ->placeholder('mesadepartes@entidad.gob.pe')
                            ->maxLength(255)
                            ->autocomplete('off')
                            ->helperText('Vacío si el servidor no pide usuario; entonces tampoco se envía la contraseña.'),
                        TextInput::make('mail_password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->maxLength(255)
                            ->placeholder(fn (): string => filled(Setting::valueOf('mail_password')) ? 'Guardada. Escriba otra solo para cambiarla.' : 'Sin contraseña guardada')
                            ->helperText('Se guarda cifrada y no vuelve a mostrarse. En Gmail o Microsoft 365 use una contraseña de aplicación.'),
                        TextInput::make('mail_from_address')
                            ->label('Correo remitente')
                            ->email()
                            ->maxLength(150)
                            ->placeholder('mesadepartes@entidad.gob.pe')
                            ->required(fn (Get $get): bool => (bool) $get('mail_enabled'))
                            ->helperText('Una cuenta que el servidor permita usar como remitente; si no, los correos se rechazan o llegan como spam.'),
                        TextInput::make('mail_from_name')
                            ->label('Nombre del remitente')
                            ->maxLength(150)
                            ->placeholder(fn (): string => Setting::valueOf('site_name') ?: (string) config('app.name'))
                            ->helperText('Vacío: el nombre oficial de la entidad.'),
                    ]),
                Section::make('Acceso al panel')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->schema([
                        Radio::make('panel_mfa_required')
                            ->label('Verificación en dos pasos')
                            ->options(self::MFA_POLICIES)
                            ->descriptions([
                                'none' => 'Cada persona decide si la activa desde «Mi perfil».',
                                'admins' => 'Recomendado. Los administradores pueden cambiar cualquier cosa, incluidas otras cuentas.',
                                'all' => 'Para entidades que publican o atienden expedientes con muchas personas.',
                            ])
                            ->required()
                            ->helperText('Quien deba usarla y aún no la tenga, tendrá que configurarla con una app como Google Authenticator la próxima vez que entre al panel. Téngala a mano antes de exigirla a los administradores.'),
                    ]),
            ])
            ->statePath('data');
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
                    ])->sticky(),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTest')
                ->label('Enviar correo de prueba')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                ->modalHeading('Probar el servidor de correo')
                ->modalDescription('Se usan los datos que están en el formulario, aunque todavía no los haya guardado.')
                ->modalSubmitActionLabel('Enviar')
                ->fillForm(fn (): array => ['to' => auth()->user()?->email])
                ->schema([
                    TextInput::make('to')->label('Enviar a')->email()->required()->maxLength(150),
                ])
                ->action(fn (array $data) => $this->sendTestMessage((string) $data['to'])),
        ];
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->form->getState();
        $changed = 0;

        foreach (Setting::query()->where('group', Setting::SYSTEM_GROUP)->get() as $setting) {
            if (! array_key_exists($setting->key, $state)) {
                continue;
            }

            $value = $state[$setting->key];
            $value = match (true) {
                $setting->key === 'mail_enabled' => $value ? '1' : '0',
                // An empty password field keeps the stored one; it is never sent to the browser to be re-saved.
                $setting->key === 'mail_password' => filled($value) ? OutgoingMail::encryptPassword((string) $value) : $setting->value,
                is_string($value) => trim($value) === '' ? null : trim($value),
                $value === null => null,
                default => (string) $value,
            };

            if ($value !== $setting->value) {
                $setting->update(['value' => $value]);
                $changed++;
            }
        }

        // Values are memoised per request; the status shown after saving must already reflect the change.
        Once::flush();
        $this->form->fill($this->storedValues());

        Notification::make()
            ->title($changed > 0 ? 'Configuración guardada' : 'No había cambios por guardar')
            ->success()
            ->send();
    }

    private function sendTestMessage(string $recipient): void
    {
        $values = $this->form->getState();

        if (blank($values['mail_host'] ?? null) || blank($values['mail_from_address'] ?? null)) {
            Notification::make()
                ->title('Faltan datos para probar')
                ->body('Complete el servidor SMTP y el correo remitente.')
                ->warning()
                ->send();

            return;
        }

        $password = filled($values['mail_password'] ?? null) ? (string) $values['mail_password'] : OutgoingMail::storedPassword();

        try {
            $mailer = Mail::build(OutgoingMail::mailerConfig($values, $password));
            $mailer->alwaysFrom((string) $values['mail_from_address'], OutgoingMail::senderName($values));
            $mailer->to($recipient)->send(new OutgoingMailTest((string) $values['mail_host']));
        } catch (Throwable $exception) {
            Notification::make()
                ->title('No se pudo enviar el correo de prueba')
                ->body('El servidor respondió: '.Str::limit($exception->getMessage(), 400))
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title('Correo de prueba enviado')
            ->body("Revise la bandeja de {$recipient}, también la carpeta de spam. Si llegó, ya puede activar y guardar la configuración.")
            ->success()
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    private function storedValues(): array
    {
        return Setting::query()->where('group', Setting::SYSTEM_GROUP)->pluck('value', 'key')
            ->map(fn (?string $value, string $key): mixed => match ($key) {
                'mail_enabled' => $value === '1',
                'mail_password' => null,
                default => $value,
            })
            ->all();
    }
}
