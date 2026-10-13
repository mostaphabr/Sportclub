<?php

namespace Tests\Feature;

use App\Models\Pitche;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase, CreatesSportClubData;

    private function tomorrow(): string
    {
        return today()->addDay()->toDateString();
    }

    public function test_slots_come_from_open_hours(): void
    {
        $pitch = new Pitche();

        $pitch->openhours = '8:00-21:00';
        $this->assertSame([8, 21], $pitch->openingRange());
        $this->assertCount(13, $pitch->slots());
        $this->assertSame(['debut' => 20, 'fin' => 21], last($pitch->slots()));

        $pitch->openhours = '9h-23h';
        $this->assertSame([9, 23], $pitch->openingRange());

        $pitch->openhours = 'whenever';
        $this->assertSame([8, 21], $pitch->openingRange(), 'falls back to the old 8-20 calendar');
    }

    public function test_player_books_an_hour_for_tomorrow(): void
    {
        $player = $this->makeUser();
        $pitch = $this->makePitch();

        $this->actingAs($player)->get(route('reservation.show', $pitch->name))
            ->assertOk()
            ->assertSee(today()->addDay()->format('l d/m/Y'));

        $this->actingAs($player)->post('/reservation', ['pitche_id' => $pitch->id, 'hour' => '10-11'])
            ->assertRedirect('/reservation')
            ->assertSessionHas('message');

        $reservation = Reservation::firstOrFail();
        $this->assertSame($this->tomorrow(), $reservation->date->toDateString());
        $this->assertSame(10, $reservation->debut);
        $this->assertSame(11, $reservation->fin);
        $this->assertSame($this->tomorrow(), Reservation::query()->toBase()->value('date'), 'stored as Y-m-d');
    }

    public function test_the_same_hour_cannot_be_booked_twice_for_the_same_day(): void
    {
        $pitch = $this->makePitch();
        $this->makeReservation($this->makeUser(), $pitch, $this->tomorrow(), 10);

        $this->actingAs($this->makeUser())->post('/reservation', ['pitche_id' => $pitch->id, 'hour' => '10-11'])
            ->assertSessionHasErrors('hour');

        $this->assertSame(1, Reservation::count());
    }

    public function test_the_database_also_refuses_a_double_booking(): void
    {
        $pitch = $this->makePitch();
        $this->makeReservation($this->makeUser(), $pitch, $this->tomorrow(), 10);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->makeReservation($this->makeUser(), $pitch, $this->tomorrow(), 10);
    }

    public function test_an_hour_booked_on_an_earlier_day_is_free_again(): void
    {
        $pitch = $this->makePitch();
        $this->makeReservation($this->makeUser(), $pitch, today()->toDateString(), 10);

        $this->actingAs($this->makeUser())->post('/reservation', ['pitche_id' => $pitch->id, 'hour' => '10-11'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Reservation::count());
    }

    public function test_booking_page_marks_taken_hours(): void
    {
        $pitch = $this->makePitch();
        $this->makeReservation($this->makeUser(), $pitch, $this->tomorrow(), 10);

        $this->actingAs($this->makeUser())->get(route('reservation.show', $pitch->name))
            ->assertSee('10:00 - 11:00  (booked)', false)
            ->assertSee('12 of 13 hours still free');
    }

    public function test_hours_outside_opening_or_malformed_are_rejected(): void
    {
        $player = $this->makeUser();
        $pitch = $this->makePitch(null, ['openhours' => '10:00-18:00']);

        foreach (['8-9', '18-19', '10-12', 'abc', ''] as $hour) {
            $this->actingAs($player)->post('/reservation', ['pitche_id' => $pitch->id, 'hour' => $hour])
                ->assertSessionHasErrors('hour');
        }

        $this->assertSame(0, Reservation::count());
    }

    public function test_providers_and_guests_cannot_book(): void
    {
        $pitch = $this->makePitch();

        $this->post('/reservation', ['pitche_id' => $pitch->id, 'hour' => '10-11'])->assertRedirect('/login');
        $this->actingAs($this->makeUser('provider'))
            ->post('/reservation', ['pitche_id' => $pitch->id, 'hour' => '10-11'])
            ->assertForbidden();

        $this->assertSame(0, Reservation::count());
    }

    public function test_player_sees_and_cancels_own_upcoming_reservation(): void
    {
        $player = $this->makeUser();
        $pitch = $this->makePitch();
        $reservation = $this->makeReservation($player, $pitch, $this->tomorrow(), 15);
        $old = $this->makeReservation($player, $pitch, today()->subDays(3)->toDateString(), 15);

        $this->actingAs($player)->get('/MyReservations')
            ->assertOk()
            ->assertSee('15:00 - 16:00')
            ->assertSee('Past reservations')
            ->assertSee('Cancel reservation');

        // past ones can't be cancelled
        $this->actingAs($player)->delete('/reservation/'.$old->id)->assertForbidden();

        // someone else can't cancel mine
        $this->actingAs($this->makeUser())->delete('/reservation/'.$reservation->id)->assertForbidden();

        $this->actingAs($player)->from('/MyReservations')->delete('/reservation/'.$reservation->id)
            ->assertRedirect('/MyReservations');
        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
    }

    public function test_provider_sees_and_cancels_bookings_on_their_own_pitch_only(): void
    {
        $provider = $this->makeUser('provider');
        $mine = $this->makePitch($provider);
        $other = $this->makePitch();
        $player = $this->makeUser();
        $onMine = $this->makeReservation($player, $mine, $this->tomorrow(), 9);
        $onOther = $this->makeReservation($player, $other, $this->tomorrow(), 9);

        $this->actingAs($provider)->get('/ProviderDashboard')->assertOk();
        $this->actingAs($provider)->get('/MyTerrainReservations/'.$mine->id)->assertOk()->assertSee($player->name);
        $this->actingAs($provider)->get('/MyTerrainReservations/'.$other->id)->assertNotFound();

        $this->actingAs($provider)->delete('/reservation/'.$onOther->id)->assertForbidden();
        $this->actingAs($provider)->delete('/reservation/'.$onMine->id)->assertRedirect();
        $this->assertDatabaseMissing('reservations', ['id' => $onMine->id]);
    }

    public function test_admin_cancels_any_reservation(): void
    {
        $reservation = $this->makeReservation($this->makeUser(), $this->makePitch(), $this->tomorrow(), 9);

        $this->actingAs($this->makeUser('admin'))->delete('/reservation/'.$reservation->id)->assertRedirect();
        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
    }
}
