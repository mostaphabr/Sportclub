<?php

namespace Tests\Feature;

use App\Models\MatchRequest;
use App\Models\Reservation;
use App\Notifications\JoinRequestAnswered;
use App\Notifications\JoinRequestReceived;
use App\Notifications\ReservationCancelled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TeammatesTest extends TestCase
{
    use RefreshDatabase, CreatesSportClubData;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function openMatch(int $playersNeeded = 2): Reservation
    {
        $booker = $this->makeUser('user', ['name' => 'Organizer Omar']);
        $reservation = $this->makeReservation($booker, $this->makePitch(null, ['capacity' => 10]), today()->addDay()->toDateString(), 19);
        $reservation->players_needed = $playersNeeded;
        $reservation->match_note = 'friendly game';
        $reservation->save();

        return $reservation->fresh();
    }

    public function test_booking_can_ask_for_teammates(): void
    {
        $player = $this->makeUser();
        $pitch = $this->makePitch(null, ['capacity' => 10]);

        $this->actingAs($player)->post('/reservation', [
            'pitche_id' => $pitch->id, 'hour' => '20-21', 'players_needed' => 3, 'match_note' => 'need a keeper',
        ])->assertSessionHasNoErrors();

        $reservation = Reservation::firstOrFail();
        $this->assertSame(3, $reservation->players_needed);
        $this->assertSame('need a keeper', $reservation->match_note);

        // more players than the pitch holds is refused
        $this->actingAs($this->makeUser())->post('/reservation', [
            'pitche_id' => $pitch->id, 'hour' => '10-11', 'players_needed' => 10,
        ])->assertSessionHasErrors('players_needed');
    }

    public function test_open_match_is_listed_and_a_player_asks_to_join(): void
    {
        $match = $this->openMatch();
        $player = $this->makeUser('user', ['name' => 'Joiner Jamal']);

        $this->actingAs($player)->get('/matches')
            ->assertOk()
            ->assertSee($match->pitche->name)
            ->assertSee('2 spots left')
            ->assertSee('friendly game');

        // the organizer doesn't see their own match in the list
        $this->actingAs($match->user)->get('/matches')->assertDontSee(route('matches.join', $match), false);

        $this->actingAs($player)->post("/matches/{$match->id}/join", ['message' => 'I play defender'])
            ->assertSessionHas('message');

        $request = MatchRequest::firstOrFail();
        $this->assertSame('pending', $request->status);
        Notification::assertSentTo($match->user, JoinRequestReceived::class);

        // asking twice is refused
        $this->actingAs($player)->post("/matches/{$match->id}/join")->assertSessionHas('error');
        $this->assertSame(1, MatchRequest::count());

        // the organizer sees the request
        $this->actingAs($match->user)->get('/MyReservations')
            ->assertSee('Joiner Jamal')
            ->assertSee('I play defender');
    }

    public function test_organizer_accepts_until_the_match_is_full(): void
    {
        $match = $this->openMatch(1);
        $first = $this->makeUser();
        $second = $this->makeUser();

        $this->actingAs($first)->post("/matches/{$match->id}/join");
        $this->actingAs($second)->post("/matches/{$match->id}/join");
        [$r1, $r2] = MatchRequest::orderBy('id')->get();

        // only the organizer can answer
        $this->actingAs($second)->patch("/match-requests/{$r1->id}/accept")->assertForbidden();

        $this->actingAs($match->user)->patch("/match-requests/{$r1->id}/accept")->assertSessionHas('message');
        $this->assertSame('accepted', $r1->fresh()->status);
        Notification::assertSentTo($first, JoinRequestAnswered::class);

        // full now
        $this->actingAs($match->user)->patch("/match-requests/{$r2->id}/accept")->assertSessionHas('error');
        $this->assertSame('pending', $r2->fresh()->status);
        $this->actingAs($match->user)->patch("/match-requests/{$r2->id}/decline")->assertSessionHas('message');
        $this->assertSame('declined', $r2->fresh()->status);

        // a full match disappears from the list; the accepted player sees it in My Reservations
        $this->actingAs($this->makeUser())->get('/matches')->assertDontSee($match->pitche->name);
        $this->actingAs($first)->get('/MyReservations')->assertSee("Matches I'm joining", false)->assertSee($match->pitche->name);
    }

    public function test_organizer_changes_spots_and_players_can_leave(): void
    {
        $match = $this->openMatch(2);
        $player = $this->makeUser();
        $this->actingAs($player)->post("/matches/{$match->id}/join");
        $request = MatchRequest::firstOrFail();
        $this->actingAs($match->user)->patch("/match-requests/{$request->id}/accept");

        // can't go below the number already accepted
        $this->actingAs($match->user)->patch("/reservation/{$match->id}/team", ['players_needed' => 0])
            ->assertSessionHasErrors('players_needed');

        $this->actingAs($match->user)->patch("/reservation/{$match->id}/team", ['players_needed' => 4, 'match_note' => 'bring white shirts'])
            ->assertSessionHasNoErrors();
        $this->assertSame(4, $match->fresh()->players_needed);

        // someone else can't change it
        $this->actingAs($player)->patch("/reservation/{$match->id}/team", ['players_needed' => 1])->assertForbidden();

        // the player leaves
        $this->actingAs($player)->delete("/match-requests/{$request->id}")->assertSessionHas('message');
        $this->assertSame(0, MatchRequest::count());
    }

    public function test_providers_cannot_join_and_started_matches_are_closed(): void
    {
        $match = $this->openMatch();

        $this->actingAs($this->makeUser('provider'))->post("/matches/{$match->id}/join")->assertForbidden();

        $past = $this->makeReservation($this->makeUser(), $this->makePitch(), today()->subDay()->toDateString(), 10);
        $past->players_needed = 3;
        $past->save();
        $this->actingAs($this->makeUser())->post("/matches/{$past->id}/join")->assertSessionHas('error');
    }

    public function test_accepted_players_are_told_when_the_booking_is_cancelled(): void
    {
        $match = $this->openMatch();
        $player = $this->makeUser();
        $this->actingAs($player)->post("/matches/{$match->id}/join");
        $this->actingAs($match->user)->patch('/match-requests/'.MatchRequest::first()->id.'/accept');

        $this->actingAs($match->user)->delete("/reservation/{$match->id}")->assertRedirect();

        Notification::assertSentTo($player, ReservationCancelled::class);
        Notification::assertSentTo($match->pitche->user, ReservationCancelled::class);
        Notification::assertNotSentTo($match->user, ReservationCancelled::class);
        $this->assertSame(0, MatchRequest::count(), 'requests are deleted with the booking');
    }
}
