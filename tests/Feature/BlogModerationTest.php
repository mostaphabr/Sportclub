<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogModerationTest extends TestCase
{
    use RefreshDatabase, CreatesSportClubData;

    public function test_admin_removes_any_post_and_its_comments(): void
    {
        $admin = $this->makeUser('admin');
        $author = $this->makeUser();
        $post = $this->makePost($author, 'Spam post');
        $this->makeComment($author, $post);

        $this->actingAs($admin)->get('/posts/'.$post->id)->assertSee('Remove post (admin)');

        $this->actingAs($admin)->from('/posts/'.$post->id)->delete('/posts/'.$post->id)
            ->assertRedirect('/posts');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_author_deletes_own_post_but_not_others(): void
    {
        $author = $this->makeUser();
        $someoneElse = $this->makeUser();
        $mine = $this->makePost($author, 'Mine');
        $theirs = $this->makePost($someoneElse, 'Theirs');

        $this->actingAs($author)->get('/posts')
            ->assertOk()
            ->assertSee('Mine');

        $this->actingAs($author)->delete('/posts/'.$theirs->id)->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $theirs->id]);

        $this->actingAs($author)->delete('/posts/'.$mine->id)->assertRedirect();
        $this->assertDatabaseMissing('posts', ['id' => $mine->id]);
    }

    public function test_delete_button_only_shows_to_admin_or_author(): void
    {
        $author = $this->makeUser();
        $reader = $this->makeUser();
        $post = $this->makePost($author);

        $this->actingAs($reader)->get('/posts/'.$post->id)->assertOk()->assertDontSee('Delete my post')->assertDontSee('Remove post (admin)');
        $this->actingAs($author)->get('/posts/'.$post->id)->assertSee('Delete my post');
    }

    public function test_comments_can_be_removed_by_admin_or_their_author(): void
    {
        $admin = $this->makeUser('admin');
        $author = $this->makeUser();
        $reader = $this->makeUser();
        $post = $this->makePost($author);
        $first = $this->makeComment($reader, $post, 'first');
        $second = $this->makeComment($reader, $post, 'second');

        $this->actingAs($author)->delete('/comment/'.$first->id)->assertForbidden();

        $this->actingAs($reader)->delete('/comment/'.$first->id)->assertRedirect();
        $this->actingAs($admin)->delete('/comment/'.$second->id)->assertRedirect();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comment_needs_an_existing_post(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/comment', ['comment' => 'hi', 'id' => 999])
            ->assertSessionHasErrors('id');
    }
}
