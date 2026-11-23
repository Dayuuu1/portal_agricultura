<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Outgoing mail server set up by an administrator in «Correo y acceso». While it is switched off or incomplete,
 * the mailer of the server's .env file keeps working, so nothing changes until the entity sets its server up.
 */
final class OutgoingMail
{
    /**
     * Name of the mailer built from the stored values; the .env mailers are left untouched.
     */
    public const MAILER = 'portal';

    public const ENCRYPTIONS = [
        'tls' => 'STARTTLS',
        'ssl' => 'SSL/TLS',
        'none' => 'Sin cifrado obligatorio',
    ];

    public const DEFAULT_PORTS = ['tls' => 587, 'ssl' => 465, 'none' => 25];

    /**
     * Mail is sent while the page answers, so a server that does not reply must not keep the citizen waiting.
     */
    private const TIMEOUT_SECONDS = 15;

    public static function isEnabled(): bool
    {
        return Setting::valueOf('mail_enabled') === '1'
            && filled(Setting::valueOf('mail_host'))
            && filled(Setting::valueOf('mail_from_address'));
    }

    /**
     * Points the application's mail at the stored server. Called once, when the mail manager is first needed.
     */
    public static function apply(): void
    {
        if (! self::isEnabled()) {
            return;
        }

        $values = Setting::allCached();

        config([
            'mail.default' => self::MAILER,
            'mail.mailers.'.self::MAILER => self::mailerConfig($values, self::storedPassword()),
            'mail.from.address' => $values['mail_from_address'],
            'mail.from.name' => self::senderName($values),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values  Stored settings or the unsaved form, keyed like the settings.
     * @return array<string, mixed>
     */
    public static function mailerConfig(array $values, ?string $password): array
    {
        $encryption = array_key_exists((string) ($values['mail_encryption'] ?? ''), self::ENCRYPTIONS) ? (string) $values['mail_encryption'] : 'tls';
        $username = filled($values['mail_username'] ?? null) ? (string) $values['mail_username'] : null;

        return [
            'transport' => 'smtp',
            'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'url' => null,
            'host' => (string) ($values['mail_host'] ?? ''),
            'port' => (int) ($values['mail_port'] ?? 0) ?: self::DEFAULT_PORTS[$encryption],
            'username' => $username,
            'password' => $username !== null ? $password : null,
            // STARTTLS is demanded, not just attempted, so the password never travels in clear text.
            'require_tls' => $encryption === 'tls',
            'timeout' => self::TIMEOUT_SECONDS,
            'local_domain' => config('mail.mailers.smtp.local_domain'),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function senderName(array $values): string
    {
        return filled($values['mail_from_name'] ?? null)
            ? (string) $values['mail_from_name']
            : (Setting::valueOf('site_name') ?: (string) config('app.name'));
    }

    public static function encryptPassword(string $password): string
    {
        return Crypt::encryptString($password);
    }

    /**
     * Stored password, or null when there is none or it was encrypted with another APP_KEY.
     */
    public static function storedPassword(): ?string
    {
        $encrypted = Setting::valueOf('mail_password');

        if (blank($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * What the panel shows about where mail goes right now.
     *
     * @return array{state: 'database'|'env'|'not-sent', description: string}
     */
    public static function status(): array
    {
        if (self::isEnabled()) {
            return [
                'state' => 'database',
                'description' => 'Los correos salen por '.Setting::valueOf('mail_host').' con el remitente '.Setting::valueOf('mail_from_address').'.',
            ];
        }

        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            return [
                'state' => 'not-sent',
                'description' => 'Ahora los correos no llegan a nadie: se guardan en el registro del servidor (MAIL_MAILER='.$mailer.'). '
                    .'Constancias, avisos y enlaces para restablecer contraseñas no se entregan hasta configurar un servidor.',
            ];
        }

        return [
            'state' => 'env',
            'description' => 'Se usa el servidor definido en el archivo .env del servidor (MAIL_MAILER='.$mailer.').',
        ];
    }
}
