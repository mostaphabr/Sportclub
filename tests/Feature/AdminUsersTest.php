<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase, CreatesSportClubData;

    public function test_guests_are_sent_to_login_and_non_admins_are_blocked(): void
    {
        $this->get('/AdminDashbord')->assertRedirect('/login');

        foreach (['user', 'provider'] as $role) {
            $user = $this->makeUser($role);

            $this->actingAs($user)->get('/AdminDashbord')->assertForbidden();
            $this->actingAs($user)->post('/admin/users', [
                'name' => 'Hacker', 'email' => 'h@example.com', 'role' => 'admin',
            ])->assertForbidden();
            $this->actingAs($user)->post('/AdminDashbord', ['name' => 'x'])->assertForbidden();
        }

        $this->assertDatabaseMissing('users', ['email' => 'h@example.com']);
    }

    public function test_every_dashboard_tab_renders_for_admin(): void
    {
        $admin = $this->makeUser('admin');
        $player = $this->makeUser();
        $pitch = $this->makePitch();
        $this->makeReservation($player, $pitch, today()->addDay()->toDateString(), 10);
        $this->makePost($player, 'Great game');

        foreach (['overview', 'pitches', 'users', 'posts', 'reservations'] as $tab) {
            $this->actingAs($admin)->get('/AdminDashbord?tab='.$tab)->assertOk();
        }

        $this->actingAs($admin)->get('/AdminDashbord?tab=posts')->assertSee('Great game');
        $this->actingAs($admin)->get('/AdminDashbord?tab=reservations')->assertSee($pitch->name);
        $this->actingAs($admin)->get('/AdminDashbord?tab=users&q='.urlencode($player->email))->assertSee($player->name);
    }

    public function test_admin_adds_a_user_with_a_generated_password(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Karim Provider',
            'email' => 'Karim@Example.com',
            'role' => 'provider',
        ]);

        $response->assertRedirect(route('AdminDashbord.index', ['tab' => 'users']));
        $response->assertSessionHas('generated_password');

        $credentials = session('generated_password');
        $this->assertSame('karim@example.com', $credentials['email']);
        $this->assertSame(12, strlen($credentials['password']));

        $user = User::where('email', 'karim@example.com')->firstOrFail();
        $this->assertSame('provider', $user->role);
        $this->assertNotNull($user->profile, 'a profile is created like on registration');
        $this->assertTrue(Hash::check($credentials['password'], $user->password));

        // The page shows the password once
        $this->actingAs($admin)->withSession(['generated_password' => $credentials])
            ->get('/AdminDashbord?tab=users')->assertSee($credentials['password']);

        // ...and the new user can log in with it
        auth()->logout();
        $this->post('/login', ['email' => 'karim@example.com', 'password' => $credentials['password']])
            ->assertRedirect('/reservation');
        $this->assertAuthenticatedAs($user);
    }

    public function test_add_user_validation(): void
    {
        $admin = $this->makeUser('admin');
        $existing = $this->makeUser();

        $this->actingAs($admin)->post('/admin/users', [
            'name' => '', 'email' => $existing->email, 'role' => 'superuser',
        ])->assertSessionHasErrorsIn('addUser', ['name', 'email', 'role']);
    }

    public function test_admin_changes_roles_but_not_their_own(): void
    {
        $admin = $this->makeUser('admin');
        $player = $this->makeUser();

        $this->actingAs($admin)->patch("/admin/users/{$player->id}/role", ['role' => 'provider'])
            ->assertSessionHas('message');
        $this->assertSame('provider', $player->fresh()->role);

        $this->actingAs($admin)->patch("/admin/users/{$admin->id}/role", ['role' => 'user'])
            ->assertSessionHas('error');
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_provider_with_pitches_keeps_role_until_pitches_are_moved(): void
    {
        $admin = $this->makeUser('admin');
        $provider = $this->makeUser('provider');
        $this->makePitch($provider);

        $this->actingAs($admin)->patch("/admin/users/{$provider->id}/role", ['role' => 'user'])
            ->assertSessionHas('error');
        $this->assertSame('provider', $provider->fresh()->role);
    }

    public function test_admin_resets_a_password(): void
    {
        $admin = $this->makeUser('admin');
        $player = $this->makeUser();

        $this->actingAs($admin)->patch("/admin/users/{$player->id}/password")
            ->assertSessionHas('generated_password');

        $this->assertTrue(Hash::check(session('generated_password')['password'], $player->fresh()->password));
    }

    public function test_admin_deletes_users_but_not_themselves(): void
    {
        $admin = $this->makeUser('admin');
        $player = $this->makeUser();
        $this->makePost($player);

        $this->actingAs($admin)->delete("/admin/users/{$player->id}")->assertSessionHas('message');
        $this->assertDatabaseMissing('users', ['id' => $player->id]);
        $this->assertDatabaseCount('posts', 0);

        $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
