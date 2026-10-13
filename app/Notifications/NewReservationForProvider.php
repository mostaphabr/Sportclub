<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the pitch owner (provider), when someone books their pitch. */
class NewReservationForProvider extends Notification
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

        return (new MailMessage)
            ->subject('New booking: '.$r->pitche->name.' on '.$r->date->format('d/m').' at '.sprintf('%02d:00', $r->debut))
            ->greeting('Hello '.$notifiable->name.'!')
            ->line($r->user->name.' booked **'.$r->pitche->name.'** for '.$r->date->format('l d/m/Y').', '.$r->timeRange().'.')
            ->line('Phone: '.(optional($r->user->profile)->phone_number ?: 'not given'))
            ->action('See bookings for this terrain', route('MyTerrainReservations', $r->pitche_id));
    }
}
