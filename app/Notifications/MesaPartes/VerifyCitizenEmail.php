<?php

namespace App\Notifications\MesaPartes;

use App\Models\Citizen;
use App\Models\Setting;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

/**
 * Confirms that the e-mail of a new Mesa de Partes account belongs to the person who registered it.
 */
class VerifyCitizenEmail extends VerifyEmail
{
    /**
     * @param  Citizen  $notifiable
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute('mesa-partes.verification.verify', now()->addHours(24), [
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]);
    }

    /**
     * @param  string  $url
     */
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirme su correo para la Mesa de Partes Virtual')
            ->greeting('Bienvenido(a) a la Mesa de Partes Virtual')
            ->line('Para activar su cuenta y presentar documentos, confirme que este es su correo electrónico.')
            ->action('Confirmar mi correo', $url)
            ->line('El enlace vence en 24 horas. Si usted no creó esta cuenta, ignore este mensaje.')
            ->salutation('Atentamente, '.Setting::valueOf('site_name', config('app.name')));
    }
}
