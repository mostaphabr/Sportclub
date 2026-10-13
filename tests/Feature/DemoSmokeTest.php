<?php

namespace Tests\Feature;

use App\Models\Pitche;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Seeds the demo data and opens every main page as each kind of user.
 */
class DemoSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_can_run_twice(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame(6, User::count());
        $this->assertSame(4, Pitche::count());
        $this->assertSame(3, Post::count());
    }

    public function test_every_page_opens_for_every_role(): void
    {
        $this->seed(DemoSeeder::class);
        $pitch = Pitche::first();
        $post = Post::first();
        $admin = User::where('email', 'admin@sportclub.test')->first();
        $provider = User::where('email', 'provider@sportclub.test')->first();
        $player = User::where('email', 'player@sportclub.test')->first();

        foreach (['/', '/reservation', '/reservation?sort=rating&size=10', '/login', '/register', '/contact'] as $url) {
            $this->get($url)->assertOk();
        }

        $common = ['/', '/reservation', route('reservation.show', $pitch->name), '/posts', '/posts/'.$post->id,
            route('profile', $player), '/contact'];

        foreach ($common as $url) {
            $this->actingAs($player)->get($url)->assertOk();
            $this->actingAs($provider)->get($url)->assertOk();
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->actingAs($player)->get('/matches')->assertOk();
        $this->actingAs($player)->get('/MyReservations')->assertOk();
        $this->actingAs($provider)->get('/ProviderDashboard')->assertOk();
        $this->actingAs($provider)->get('/MyTerrainReservations/'.$provider->pitches()->first()->id)->assertOk();

        foreach (['overview', 'pitches', 'users', 'posts', 'reservations', 'messages'] as $tab) {
            $this->actingAs($admin)->get('/AdminDashbord?tab='.$tab)->assertOk();
        }
        $this->actingAs($admin)->get('/AdminDashbord/'.$pitch->id.'/edit')->assertOk();
    }
}
