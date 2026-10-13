<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProfileAndPostsTest extends TestCase
{
    use RefreshDatabase, CreatesSportClubData;

    public function test_profile_page_uses_the_user_id_even_when_profile_ids_differ(): void
    {
        // Make profile ids and user ids different on purpose.
        $noProfile = User::factory()->create();
        $alice = $this->makeUser('user', ['name' => 'Alice Striker']);
        $bob = $this->makeUser('user', ['name' => 'Bob Keeper']);
        $this->assertNotSame($alice->id, $alice->profile->id);

        $this->actingAs($bob)->get(route('profile', $alice))
            ->assertOk()
            ->assertSee('Alice Striker')
            ->assertDontSee('Bob Keeper</h1>', false);

        // A user without a profile row still gets a page (an empty profile is created).
        $this->actingAs($bob)->get(route('profile', $noProfile))->assertOk();
        $this->assertNotNull($noProfile->fresh()->profile);
    }

    public function test_blog_links_point_to_the_author_profile(): void
    {
        User::factory()->create(); // shift ids
        $author = $this->makeUser('user', ['name' => 'Yassine Author']);
        $this->makePost($author, 'Derby night');

        $this->actingAs($author)->get('/posts')->assertSee(route('profile', $author), false);
    }

    public function test_profile_update_checks_email_and_replaces_the_picture(): void
    {
        $taken = $this->makeUser('user', ['email' => 'taken@example.com']);
        $user = $this->makeUser();

        $this->actingAs($user)->put('/store', [
            'name' => 'New Name', 'email' => 'taken@example.com',
        ])->assertSessionHasErrors('email');

        $this->actingAs($user)->put('/store', [
            'name' => 'New Name', 'email' => $user->email, 'phone' => '0600000000',
            'image' => UploadedFile::fake()->image('me.jpg'),
        ])->assertSessionHasNoErrors();

        $first = $user->fresh()->profile->profile_picture;
        $this->assertFileExists(public_path('images/profile/'.$first));

        $this->actingAs($user)->put('/store', [
            'name' => 'New Name', 'email' => $user->email, 'phone' => '0600000000',
            'image' => UploadedFile::fake()->image('me2.jpg'),
        ])->assertSessionHasNoErrors();

        $second = $user->fresh()->profile->profile_picture;
        $this->assertNotSame($first, $second);
        $this->assertFileDoesNotExist(public_path('images/profile/'.$first), 'old picture is deleted');
        $this->assertSame('New Name', $user->fresh()->name);
        $this->assertSame('0600000000', $user->fresh()->profile->phone_number);

        File::delete(public_path('images/profile/'.$second));
    }

    public function test_author_edits_their_post_and_others_cannot(): void
    {
        $author = $this->makeUser();
        $admin = $this->makeUser('admin');
        $other = $this->makeUser();
        $post = $this->makePost($author, 'Old title');

        $this->actingAs($author)->get('/posts/'.$post->id)->assertSee('Edit my post');
        $this->actingAs($author)->get('/posts/'.$post->id.'/edit')->assertOk()->assertSee('Old title');

        $this->actingAs($other)->get('/posts/'.$post->id.'/edit')->assertForbidden();
        $this->actingAs($admin)->put('/posts/'.$post->id, ['title' => 'Hacked', 'description' => 'x'])->assertForbidden();

        $this->actingAs($author)->put('/posts/'.$post->id, ['title' => 'New title', 'description' => 'Updated text'])
            ->assertRedirect('/posts/'.$post->id);

        $this->assertSame('New title', $post->fresh()->title);
        $this->assertSame('test.jpg', $post->fresh()->image, 'image kept when none is sent');
    }

    public function test_layout_loads_css_with_the_right_folder_case(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('SportClub/assets/css/main.css', false)
            ->assertDontSee('Sportclub/assets/css/main.css', false);
    }
}
