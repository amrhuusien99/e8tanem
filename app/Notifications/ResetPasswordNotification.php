<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $verificationCode)
    {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Password Reset Code')
            ->line('Your password reset verification code is:')
            ->line($this->verificationCode)
            ->line('This code will expire in 15 minutes.')
            ->line('If you did not request this code, please ignore this email.');
    }
}