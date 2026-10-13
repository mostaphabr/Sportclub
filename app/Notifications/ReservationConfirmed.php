<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the player, after booking. */
class ReservationConfirmed extends Notification
{
    public function __construct(public Reservation $reservation)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->reservation;

        $mail = (new MailMessage)
            ->subject('Booking confirmed: '.$r->pitche->name.' on '.$r->date->format('d/m'))
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your booking is confirmed:')
            ->line('**'.$r->pitche->name.'** ('.$r->pitche->localisation.')')
            ->line($r->date->format('l d/m/Y').', '.$r->timeRange());

        if ($r->price !== null) {
            $mail->line('Price: '.$r->price.' '.config('sportclub.currency', 'DH'));
        }

        return $mail
            ->action('See my reservations', route('MyReservations'))
            ->line('Need to cancel? You can do it from My Reservations until the day of the match.');
    }
}
