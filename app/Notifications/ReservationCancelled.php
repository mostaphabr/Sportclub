<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the people concerned when a booking is cancelled. */
class ReservationCancelled extends Notification
{
    public function __construct(
        public string $pitchName,
        public string $when,
        public string $cancelledBy,
    ) {
    }

    public static function for(Reservation $reservation, string $cancelledBy): self
    {
        return new self(
            $reservation->pitche->name,
            $reservation->date->format('l d/m/Y').', '.$reservation->timeRange(),
            $cancelledBy,
        );
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Booking cancelled: '.$this->pitchName)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('The booking at **'.$this->pitchName.'** on '.$this->when.' has been cancelled by '.$this->cancelledBy.'.')
            ->action('Find another terrain', route('reservation.index'));
    }
}
