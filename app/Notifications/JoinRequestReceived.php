<?php

namespace App\Notifications;

use App\Models\MatchRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the booker: someone wants to join their match. */
class JoinRequestReceived extends Notification
{
    public function __construct(public MatchRequest $request)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->request->reservation;

        $mail = (new MailMessage)
            ->subject($this->request->user->name.' wants to join your match')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line($this->request->user->name.' asked to join your match at **'.$r->pitche->name.'** on '.$r->date->format('l d/m').', '.$r->timeRange().'.');

        if ($this->request->message) {
            $mail->line('Message: "'.$this->request->message.'"');
        }

        return $mail->action('Accept or decline', route('MyReservations'));
    }
}
