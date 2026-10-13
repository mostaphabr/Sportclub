<?php

namespace Tests\Feature;

use App\Models\MatchRequest;
use App\Models\Review;
use App\Notifications\NewReservationForProvider;
use App\Notifications\ReservationConfirmed;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReviewsSearchPricesTest extends TestCase
{
    use RefreshDatabase, CreatesSportClubData;

    // ---------------------------------------------------------------- reviews

    public function test_only_players_who_played_can_review(): void
    {
        $pitch = $this->makePitch();
        $played = $this->makeUser();
        $stranger = $this->makeUser();
        $this->makeReservation($played, $pitch, today()->subDays(2)->toDateString(), 18);

        $this->actingAs($stranger)->post("/terrains/{$pitch->id}/reviews", ['rating' => 5])->assertSessionHasErrors('rating');
        $this->assertSame(0, Review::count());

        $this->actingAs($played)->get(route('reservation.show', $pitch->name))->assertSee('rate this terrain');

        $this->actingAs($played)->post("/terrains/{$pitch->id}/reviews", ['rating' => 4, 'comment' => 'Good grass'])
            ->assertRedirect();
        // reviewing again updates the same review
        $this->actingAs($played)->post("/terrains/{$pitch->id}/reviews", ['rating' => 5, 'comment' => 'Even better'])
            ->assertRedirect();

        $this->assertSame(1, Review::count());
        $this->assertSame(5, Review::first()->rating);

        $this->actingAs($stranger)->get(route('reservation.show', $pitch->name))
            ->assertSee('Even better')
            ->assertSee('5.0');
    }

    public function test_accepted_teammates_can_review_too(): void
    {
        $pitch = $this->makePitch();
        $booking = $this->makeReservation($this->makeUser(), $pitch, today()->subDay()->toDateString(), 18);
        $mate = $this->makeUser();
        $request = new MatchRequest();
        $request->reservation_id = $booking->id;
        $request->user_id = $mate->id;
        $request->status = 'accepted';
        $request->save();

        $this->actingAs($mate)->post("/terrains/{$pitch->id}/reviews", ['rating' => 3])->assertSessionHasNoErrors();
        $this->assertSame(1, Review::count());
    }

    public function test_rating_must_be_1_to_5_and_admin_can_delete_reviews(): void
    {
        $pitch = $this->makePitch();
        $player = $this->makeUser();
        $this->makeReservation($player, $pitch, today()->subDays(2)->toDateString(), 18);

        $this->actingAs($player)->post("/terrains/{$pitch->id}/reviews", ['rating' => 9])->assertSessionHasErrors('rating');

        $this->actingAs($player)->post("/terrains/{$pitch->id}/reviews", ['rating' => 1, 'comment' => 'rude words']);
        $review = Review::firstOrFail();

        $this->actingAs($this->makeUser())->delete("/reviews/{$review->id}")->assertForbidden();
        $this->actingAs($this->makeUser('admin'))->delete("/reviews/{$review->id}")->assertRedirect();
        $this->assertSame(0, Review::count());
    }

    public function test_pitch_list_shows_real_average_instead_of_fake_rating(): void
    {
        $pitch = $this->makePitch(null, ['name' => 'Stade Rated']);
        foreach ([4, 5] as $stars) {
            $review = new Review(['user_id' => $this->makeUser()->id, 'pitche_id' => $pitch->id, 'rating' => $stars]);
            $review->save();
        }

        $this->get('/reservation')
            ->assertOk()
            ->assertSee('4.5')
            ->assertDontSee('9/10');
    }

    // ---------------------------------------------------------------- search & filters

    public function test_terrains_can_be_searched_and_filtered(): void
    {
        $this->makePitch(null, ['name' => 'Alpha Khemisset', 'capacity' => 10, 'price_per_hour' => 100]);
        $this->makePitch(null, ['name' => 'Beta Rabat', 'capacity' => 22, 'price_per_hour' => 400]);
        $gamma = $this->makePitch(null, ['name' => 'Gamma Khemisset', 'capacity' => 10, 'price_per_hour' => 150, 'openhours' => '16:00-23:00']);

        $this->get('/reservation?q=Rabat')->assertSee('Beta Rabat')->assertDontSee('Alpha Khemisset');
        $this->get('/reservation?size=22')->assertSee('Beta Rabat')->assertDontSee('Gamma Khemisset');
        $this->get('/reservation?max_price=120')->assertSee('Alpha Khemisset')->assertDontSee('Gamma Khemisset');

        // free tomorrow at 10:00 → Gamma opens at 16:00, Alpha is booked at 10
        $this->makeReservation($this->makeUser(), \App\Models\Pitche::where('name', 'Alpha Khemisset')->first(), today()->addDay()->toDateString(), 10);
        $this->get('/reservation?hour=10')->assertSee('Beta Rabat')->assertDontSee('Alpha Khemisset')->assertDontSee('Gamma Khemisset');

        // sorted by price
        $this->get('/reservation?sort=price_desc')->assertSeeInOrder(['Beta Rabat', 'Gamma Khemisset', 'Alpha Khemisset']);
        $this->get('/reservation?q=nothing-matches')->assertSee('No terrain matches these filters');
    }

    // ---------------------------------------------------------------- prices & revenue

    public function test_price_is_saved_at_booking_and_shown_as_revenue(): void
    {
        Notification::fake();
        $provider = $this->makeUser('provider');
        $pitch = $this->makePitch($provider, ['name' => 'Paid Pitch', 'price_per_hour' => 120]);
        $player = $this->makeUser();

        $this->actingAs($player)->get(route('reservation.show', $pitch->name))->assertSee('120 DH / hour');
        $this->actingAs($player)->post('/reservation', ['pitche_id' => $pitch->id, 'hour' => '10-11'])->assertSessionHasNoErrors();

        $reservation = $pitch->reservations()->first();
        $this->assertSame(120, $reservation->price);

        // a later price change doesn't change what was paid
        $pitch->price_per_hour = 200;
        $pitch->save();
        $this->assertSame(120, $reservation->fresh()->price);

        $this->actingAs($provider)->get('/ProviderDashboard')->assertOk()->assertSee('120 DH');
        $this->actingAs($this->makeUser('admin'))->get('/AdminDashbord')->assertOk()->assertSee('120 DH');
    }

    // ---------------------------------------------------------------- emails

    public function test_booking_sends_emails_to_player_and_provider(): void
    {
        Notification::fake();
        $provider = $this->makeUser('provider');
        $pitch = $this->makePitch($provider);
        $player = $this->makeUser();

        $this->actingAs($player)->post('/reservation', ['pitche_id' => $pitch->id, 'hour' => '10-11']);

        Notification::assertSentTo($player, ReservationConfirmed::class);
        Notification::assertSentTo($provider, NewReservationForProvider::class);
    }

    public function test_registration_sends_a_welcome_email(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'New Player', 'email' => 'new@example.com',
            'password' => 'secret-password', 'password_confirmation' => 'secret-password',
        ])->assertRedirect();

        Notification::assertSentTo(\App\Models\User::where('email', 'new@example.com')->first(), WelcomeNotification::class);
    }

    public function test_a_broken_mail_server_never_breaks_a_booking(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);
        $player = $this->makeUser();
        $pitch = $this->makePitch();

        $this->actingAs($player)->post('/reservation', ['pitche_id' => $pitch->id, 'hour' => '10-11'])
            ->assertRedirect('/reservation')
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $pitch->reservations()->count());
    }

    public function test_emails_render(): void
    {
        $provider = $this->makeUser('provider');
        $player = $this->makeUser();
        $pitch = $this->makePitch($provider, ['price_per_hour' => 90]);
        $reservation = $this->makeReservation($player, $pitch, today()->addDay()->toDateString(), 18);
        $reservation->price = 90;

        $html = (string) (new ReservationConfirmed($reservation))->toMail($player)->render();
        $this->assertStringContainsString($pitch->name, $html);
        $this->assertStringContainsString('90 DH', $html);

        $html = (string) (new NewReservationForProvider($reservation))->toMail($provider)->render();
        $this->assertStringContainsString($player->name, $html);
    }
}
