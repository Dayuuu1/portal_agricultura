<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;

/**
 * «¿Olvidó su contraseña?» of the panel. It answers the same whether or not the address belongs to the staff,
 * so the form cannot be used to find out who works at the entity before trying their passwords.
 */
class RequestPasswordReset extends BaseRequestPasswordReset
{
    protected function getFailureNotification(string $status): ?Notification
    {
        if (! in_array($status, [Password::INVALID_USER, Password::RESET_THROTTLED], true)) {
            return parent::getFailureNotification($status);
        }

        // Same outcome as a real request: the success message and an empty form.
        $this->form->fill();

        return $this->getSentNotification(Password::RESET_LINK_SENT);
    }
}
