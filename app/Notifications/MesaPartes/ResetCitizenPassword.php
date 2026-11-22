<?php

namespace App\Notifications\MesaPartes;

use App\Models\Setting;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Link to choose a new password for a Mesa de Partes account.
 */
class ResetCitizenPassword extends ResetPassword
{
    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $url = route('mesa-partes.password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)
            ->subject('Restablezca su contraseña de la Mesa de Partes Virtual')
            ->greeting('Restablecer contraseña')
            ->line('Recibimos un pedido para cambiar la contraseña de su cuenta de la Mesa de Partes Virtual.')
            ->action('Elegir una nueva contraseña', $url)
            ->line('El enlace vence en 60 minutos. Si usted no lo pidió, ignore este mensaje: su contraseña seguirá siendo la misma.')
            ->salutation('Atentamente, '.Setting::valueOf('site_name', config('app.name')));
    }
}
