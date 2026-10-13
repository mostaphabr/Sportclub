<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to SportClub!')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your SportClub account is ready.')
            ->line('Book a terrain for tomorrow, find teammates for your match and share your games on the blog.')
            ->action('Find a terrain', route('reservation.index'))
            ->line('See you on the pitch!');
    }
}
