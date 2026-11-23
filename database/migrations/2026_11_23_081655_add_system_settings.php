<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Values of «Correo y acceso», which only administrators edit: the outgoing mail server (so the entity can change
 * it without touching the server's .env file) and who must use two-step verification in the panel. They start
 * switched off, so the portal keeps sending mail as before until an administrator sets the server up.
 */
return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['mail_enabled', 'Usar el servidor de correo configurado en el panel', 'boolean', '0'],
            ['mail_host', 'Servidor SMTP', 'text', null],
            ['mail_port', 'Puerto SMTP', 'number', '587'],
            ['mail_encryption', 'Cifrado de la conexión SMTP', 'text', 'tls'],
            ['mail_username', 'Usuario SMTP', 'text', null],
            ['mail_password', 'Contraseña SMTP (cifrada)', 'secret', null],
            ['mail_from_address', 'Correo remitente', 'email', null],
            ['mail_from_name', 'Nombre del remitente', 'text', null],
            ['panel_mfa_required', 'Verificación en dos pasos obligatoria', 'text', 'none'],
        ];

        foreach ($settings as $order => [$key, $label, $type, $value]) {
            Setting::query()->firstOrCreate(['key' => $key], [
                'label' => $label,
                'value' => $value,
                'group' => Setting::SYSTEM_GROUP,
                'type' => $type,
                'sort_order' => $order,
            ]);
        }
    }

    public function down(): void
    {
        Setting::query()->where('group', Setting::SYSTEM_GROUP)->delete();
    }
};
