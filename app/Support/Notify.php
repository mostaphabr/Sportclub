<?php

namespace App\Support;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

/**
 * Sends notifications (emails) without ever breaking the page:
 * if the mail server is not configured or down, the error is written
 * to storage/logs/laravel.log and the booking / request still succeeds.
 */
class Notify
{
    /**
     * @param  mixed  $notifiables  a user, a collection or an array of users (nulls are skipped)
     */
    public static function send($notifiables, Notification $notification): void
    {
        $notifiables = collect(is_iterable($notifiables) ? $notifiables : [$notifiables])->filter()->unique('id');

        if ($notifiables->isEmpty()) {
            return;
        }

        try {
            NotificationFacade::send($notifiables, $notification);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
