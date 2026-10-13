<?php

namespace App\Notifications;

use App\Models\MatchRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the player who asked to join: accepted or declined. */
class JoinRequestAnswered extends Notification
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
        $where = '**'.$r->pitche->name.'** on '.$r->date->format('l d/m').', '.$r->timeRange();

        if ($this->request->isAccepted()) {
            return (new MailMessage)
                ->subject('You are in! Match at '.$r->pitche->name)
                ->greeting('Good news '.$notifiable->name.'!')
                ->line($r->user->name.' accepted you for the match at '.$where.'.')
                ->line('Organizer phone: '.(optional($r->user->profile)->phone_number ?: 'not given'))
                ->action('See my matches', route('MyReservations'));
        }

        return (new MailMessage)
            ->subject('Match at '.$r->pitche->name.': request declined')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your request to join the match at '.$where.' was not accepted this time.')
            ->action('Find another match', route('matches.index'));
    }
}
