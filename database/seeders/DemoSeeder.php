<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\MatchRequest;
use App\Models\Pitche;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use App\Support\Uploads;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data so a fresh install looks alive:
 *   php artisan migrate --seed        (or: php artisan db:seed)
 *
 * Every demo account uses the password "password". Safe to run twice:
 * existing demo records are reused, nothing is duplicated.
 * Do NOT run it on a real production database.
 */
class DemoSeeder extends Seeder
{
    private const IMAGES = __DIR__.'/demo-images';

    public function run(): void
    {
        // ---------------------------------------------------------------- people
        $admin = $this->user('Ali Admin', 'admin@sportclub.test', 'admin', '0600000001', 'avatar-aa.jpg', 'I run SportClub.');
        $youssef = $this->user('Youssef Provider', 'provider@sportclub.test', 'provider', '0612345678', 'avatar-yp.jpg', 'Owner of Montazah and Santitique.');
        $salma = $this->user('Salma Benali', 'salma@sportclub.test', 'provider', '0698765432', 'avatar-sb.jpg', 'I manage Yasmine Arena and Zitoune Park.');
        $omar = $this->user('Omar Player', 'player@sportclub.test', 'user', '0611223344', 'avatar-op.jpg', 'Left winger, Friday 5-a-side regular.');
        $hamza = $this->user('Hamza Bouzid', 'hamza@sportclub.test', 'user', '0655667788', 'avatar-hb.jpg', 'Goalkeeper. Will dive for anything.');
        $imane = $this->user('Imane El Idrissi', 'imane@sportclub.test', 'user', '0677889900', 'avatar-ie.jpg', 'Midfielder, I love passing games.');

        // ---------------------------------------------------------------- terrains
        $montazah = $this->pitch('Montazah', $youssef, 10, 120, 'Khemisset, Montazah district', '8:00-21:00', 'pitch-montazah.jpg');
        $santitique = $this->pitch('Santitique', $youssef, 14, 180, 'Khemisset, city centre', '9:00-23:00', 'pitch-santitique.jpg');
        $yasmine = $this->pitch('Yasmine Arena', $salma, 22, 400, 'Khemisset, Yasmine 2', '10:00-22:00', 'pitch-yasmine-arena.jpg');
        $zitoune = $this->pitch('Zitoune Park', $salma, 10, 100, 'Tiflet, near the stadium', '16:00-23:00', 'pitch-zitoune-park.jpg');

        // ---------------------------------------------------------------- bookings
        $tomorrow = today()->addDay()->toDateString();

        $omarMatch = $this->booking($omar, $montazah, $tomorrow, 18, 3, 'Friendly 5-a-side, all levels welcome');
        $this->booking($hamza, $santitique, $tomorrow, 20, 2, 'Need two defenders');
        $imaneMatch = $this->booking($imane, $zitoune, $tomorrow, 19, 1, 'Looking for a goalkeeper!');
        $this->booking($hamza, $yasmine, $tomorrow, 17);

        // played matches (for reviews and revenue)
        foreach ([[$omar, $santitique, 3, 19], [$hamza, $montazah, 5, 18], [$imane, $montazah, 8, 20], [$omar, $yasmine, 10, 16], [$imane, $zitoune, 2, 21]] as [$user, $pitch, $daysAgo, $hour]) {
            $this->booking($user, $pitch, today()->subDays($daysAgo)->toDateString(), $hour);
        }

        // ---------------------------------------------------------------- find teammates
        $this->joinRequest($omarMatch, $hamza, MatchRequest::ACCEPTED, 'I can play in goal');
        $this->joinRequest($omarMatch, $imane, MatchRequest::PENDING, 'Midfielder here, count me in!');
        $this->joinRequest($imaneMatch, $omar, MatchRequest::PENDING, null);

        // ---------------------------------------------------------------- reviews
        $this->review($omar, $santitique, 5, 'Great grass and lights, clean changing rooms.');
        $this->review($hamza, $montazah, 4, 'Good pitch, a bit hard to park on Friday evenings.');
        $this->review($imane, $montazah, 5, 'Perfect size for 5-a-side.');
        $this->review($omar, $yasmine, 4, 'Real 11-a-side feeling. Expensive but worth it with a full team.');
        $this->review($imane, $zitoune, 3, 'Nice place, the nets need replacing.');

        // ---------------------------------------------------------------- blog
        $p1 = $this->post($omar, 'Friday night 5-a-side recap', "Great game at Montazah last night: 7-5 for the blues.\nThanks to everyone who came, same time next week?", 'post-sunset-ball.jpg');
        $p2 = $this->post($hamza, 'Goalkeeper available for your matches', 'I play every evening after 18:00. Send me a request in "Find a match" and I will be there.', 'post-goal.jpg');
        $this->post($imane, 'Khemisset summer cup: who is in?', 'We are building a team of 7 for the summer tournament. Training on Sundays at Santitique.', 'post-trophy.jpg');

        $this->comment($hamza, $p1, 'Well played! The last goal was amazing.');
        $this->comment($imane, $p1, 'Count me in for next week.');
        $this->comment($omar, $p2, 'Sending you a request for tomorrow!');

        // ---------------------------------------------------------------- contact
        ContactMessage::firstOrCreate(
            ['email' => 'karim@example.com', 'subject' => 'Price for a full evening?'],
            ['name' => 'Karim', 'message' => 'Hello, how much is Yasmine Arena for 3 hours on Saturday evening?'],
        );

        $this->command?->info('Demo data ready. Log in with admin@sportclub.test / password (or provider@ / player@sportclub.test).');
    }

    private function user(string $name, string $email, string $role, string $phone, string $avatar, string $bio): User
    {
        $user = User::firstOrNew(['email' => $email]);
        if (! $user->exists) {
            $user->name = $name;
            $user->password = Hash::make('password');
            $user->role = $role;
            $user->save();
        }

        $profile = $user->profile ?? new Profile();
        $profile->phone_number = $profile->phone_number ?: $phone;
        $profile->Bio = $profile->Bio ?: $bio;
        if (! $profile->profile_picture) {
            $profile->profile_picture = $this->image('profile', $avatar);
        }
        $user->profile()->save($profile);

        return $user;
    }

    private function pitch(string $name, User $owner, int $capacity, int $price, string $place, string $hours, string $image): Pitche
    {
        $pitch = Pitche::firstOrNew(['name' => $name]);
        if (! $pitch->exists) {
            $pitch->user_id = $owner->id;
            $pitch->capacity = $capacity;
            $pitch->price_per_hour = $price;
            $pitch->localisation = $place;
            $pitch->openhours = $hours;
            $pitch->image = $this->image('pitches', $image);
            $pitch->save();
        }

        return $pitch;
    }

    private function booking(User $user, Pitche $pitch, string $date, int $hour, int $playersNeeded = 0, ?string $note = null): Reservation
    {
        $existing = Reservation::where('pitche_id', $pitch->id)->whereDate('date', $date)->where('debut', $hour)->first();
        if ($existing) {
            return $existing;
        }

        $reservation = new Reservation();
        $reservation->user_id = $user->id;
        $reservation->pitche_id = $pitch->id;
        $reservation->date = $date;
        $reservation->debut = $hour;
        $reservation->fin = $hour + 1;
        $reservation->price = $pitch->price_per_hour;
        $reservation->players_needed = $playersNeeded;
        $reservation->match_note = $note;
        $reservation->save();

        return $reservation;
    }

    private function joinRequest(Reservation $reservation, User $user, string $status, ?string $message): void
    {
        if ((int) $reservation->user_id === (int) $user->id) {
            return;
        }

        $request = MatchRequest::firstOrNew(['reservation_id' => $reservation->id, 'user_id' => $user->id]);
        if (! $request->exists) {
            $request->status = $status;
            $request->message = $message;
            $request->save();
        }
    }

    private function review(User $user, Pitche $pitch, int $rating, string $comment): void
    {
        Review::firstOrCreate(['user_id' => $user->id, 'pitche_id' => $pitch->id], ['rating' => $rating, 'comment' => $comment]);
    }

    private function post(User $author, string $title, string $text, string $image): Post
    {
        $post = Post::firstOrNew(['title' => $title]);
        if (! $post->exists) {
            $post->user_id = $author->id;
            $post->description = $text;
            $post->image = $this->image('post', $image);
            $post->save();
        }

        return $post;
    }

    private function comment(User $author, Post $post, string $text): void
    {
        if (! Comment::where('post_id', $post->id)->where('user_id', $author->id)->where('content', $text)->exists()) {
            $comment = new Comment();
            $comment->user_id = $author->id;
            $comment->post_id = $post->id;
            $comment->content = $text;
            $comment->save();
        }
    }

    /** Copy a demo image into storage/app/public/{folder} and return its file name. */
    private function image(string $folder, string $file): string
    {
        $name = 'demo-'.$file;

        if (! Uploads::disk()->exists("{$folder}/{$name}") && File::exists(self::IMAGES.'/'.$file)) {
            Uploads::disk()->put("{$folder}/{$name}", File::get(self::IMAGES.'/'.$file));
        }

        return $name;
    }
}
