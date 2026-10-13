<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactAndLimitsTest extends TestCase
{
    use RefreshDatabase, CreatesSportClubData;

    public function test_contact_form_saves_the_message_for_admins(): void
    {
        $this->post('/contact', [
            'name' => 'Visitor', 'email' => 'visitor@example.com',
            'subject' => 'Opening hours', 'message' => 'Are you open on Sunday?',
        ])->assertRedirect('/contact')->assertSessionHas('message');

        $message = ContactMessage::firstOrFail();
        $this->assertNull($message->read_at);

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->get('/AdminDashbord?tab=messages')
            ->assertOk()
            ->assertSee('Opening hours')
            ->assertSee('Are you open on Sunday?');

        $this->actingAs($admin)->patch("/admin/messages/{$message->id}/read")->assertRedirect();
        $this->assertNotNull($message->fresh()->read_at);

        $this->actingAs($this->makeUser())->delete("/admin/messages/{$message->id}")->assertForbidden();
        $this->actingAs($admin)->delete("/admin/messages/{$message->id}")->assertRedirect();
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_contact_form_validation(): void
    {
        $this->post('/contact', ['email' => 'not-an-email'])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
    }

    public function test_players_can_book_at_most_two_hours_per_day(): void
    {
        $player = $this->makeUser();
        $a = $this->makePitch();
        $b = $this->makePitch();

        $this->actingAs($player)->post('/reservation', ['pitche_id' => $a->id, 'hour' => '10-11'])->assertSessionHasNoErrors();
        $this->actingAs($player)->post('/reservation', ['pitche_id' => $b->id, 'hour' => '18-19'])->assertSessionHasNoErrors();

        $this->actingAs($player)->get(route('reservation.show', $a->name))
            ->assertSee('already booked your 2 hours', false);

        $this->actingAs($player)->post('/reservation', ['pitche_id' => $a->id, 'hour' => '11-12'])
            ->assertSessionHasErrors('hour');

        $this->assertSame(2, Reservation::where('user_id', $player->id)->count());
    }

    public function test_the_limit_comes_from_config_and_admins_are_not_limited(): void
    {
        config(['sportclub.max_hours_per_day' => 1]);
        $player = $this->makeUser();
        $admin = $this->makeUser('admin');
        $pitch = $this->makePitch();

        $this->actingAs($player)->post('/reservation', ['pitche_id' => $pitch->id, 'hour' => '10-11'])->assertSessionHasNoErrors();
        $this->actingAs($player)->post('/reservation', ['pitche_id' => $pitch->id, 'hour' => '11-12'])->assertSessionHasErrors('hour');

        foreach (['12-13', '13-14', '14-15'] as $hour) {
            $this->actingAs($admin)->post('/reservation', ['pitche_id' => $pitch->id, 'hour' => $hour])->assertSessionHasNoErrors();
        }
    }
}
