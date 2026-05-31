<?php

namespace App\Notifications;

use Filament\Notifications\Auth\ResetPassword as FilamentResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends FilamentResetPasswordNotification
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset Your Password - Highcloud Vapestore')
            ->greeting('Hello!')
            ->line('We received a request to reset your password.')
            ->action('Reset Password', $this->url)
            ->line('This link expires in 60 minutes.')
            ->line("If you didn't request this, you can safely ignore this email.");
    }
}
