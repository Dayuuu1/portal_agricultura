<?php

namespace App\Support\MesaPartes;

use App\Models\Setting;

/**
 * Typed access to the Mesa de Partes settings (group «mesa_partes»), edited in the panel under
 * Mesa de partes › Configuración. Every value has a safe default so the module works before it is configured.
 */
final class MesaPartesConfig
{
    public const DEPARTMENTS = [
        'Amazonas', 'Áncash', 'Apurímac', 'Arequipa', 'Ayacucho', 'Cajamarca', 'Callao', 'Cusco', 'Huancavelica', 'Huánuco',
        'Ica', 'Junín', 'La Libertad', 'Lambayeque', 'Lima', 'Loreto', 'Madre de Dios', 'Moquegua', 'Pasco', 'Piura',
        'Puno', 'San Martín', 'Tacna', 'Tumbes', 'Ucayali',
    ];

    public static function enabled(): bool
    {
        return self::bool('mpv_enabled', false);
    }

    /**
     * Start of the office hours, shown to the public.
     */
    public static function openingTime(): string
    {
        return self::time('mpv_opening_time', '08:00');
    }

    /**
     * Documents sent after this time, or on a non-working day, are considered filed the next working day (Ley N° 31170).
     */
    public static function closingTime(): string
    {
        return self::time('mpv_closing_time', '16:00');
    }

    /**
     * Term to complete an observation (TUO de la Ley N° 27444, art. 136: two working days).
     */
    public static function cureDays(): int
    {
        return self::int('mpv_cure_days', 2, 1, 15);
    }

    /**
     * Default term to attend a document when its type has none of its own.
     */
    public static function responseDays(): int
    {
        return self::int('mpv_response_days', 30, 1, 365);
    }

    public static function maxFileKilobytes(): int
    {
        return self::int('mpv_max_file_mb', 10, 1, 50) * 1024;
    }

    public static function maxTotalKilobytes(): int
    {
        return self::int('mpv_max_total_mb', 30, 1, 200) * 1024;
    }

    public static function maxAttachments(): int
    {
        return self::int('mpv_max_attachments', 10, 0, 30);
    }

    public static function allowsLegalEntities(): bool
    {
        return self::bool('mpv_allow_legal_entities', true);
    }

    public static function publicLookup(): bool
    {
        return self::bool('mpv_public_lookup', true);
    }

    /**
     * Mailbox that hears about new and corrected documents; falls back to the institutional e-mail.
     */
    public static function staffEmail(): ?string
    {
        return Setting::valueOf('mpv_staff_email') ?: Setting::valueOf('email');
    }

    public static function codePrefix(): string
    {
        $prefix = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) Setting::valueOf('mpv_code_prefix')));

        return $prefix !== '' ? substr($prefix, 0, 10) : 'MPV';
    }

    public static function approval(): ?string
    {
        return Setting::valueOf('mpv_approval') ?: null;
    }

    public static function instructions(): ?string
    {
        return Setting::valueOf('mpv_instructions') ?: null;
    }

    /**
     * @return list<string>
     */
    public static function legalBasis(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) Setting::valueOf('mpv_legal_basis')) ?: [])));
    }

    public static function scheduleLabel(): string
    {
        return 'Lunes a viernes (días hábiles), de '.self::openingTime().' a '.self::closingTime().' horas';
    }

    /**
     * Where «Mesa de partes» buttons lead: this module once enabled, otherwise the external link configured
     * in «Datos del portal», if any.
     */
    public static function accessUrl(): ?string
    {
        if (self::enabled()) {
            return route('mesa-partes.index');
        }

        return Setting::valueOf('mesa_partes_url') ?: null;
    }

    private static function bool(string $key, bool $default): bool
    {
        $value = Setting::valueOf($key);

        return $value === null || $value === '' ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private static function int(string $key, int $default, int $min, int $max): int
    {
        $value = Setting::valueOf($key);

        return is_numeric($value) ? max($min, min($max, (int) $value)) : $default;
    }

    private static function time(string $key, string $default): string
    {
        $value = (string) Setting::valueOf($key);

        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : $default;
    }
}
