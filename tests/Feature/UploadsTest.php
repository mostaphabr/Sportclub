<?php

namespace Tests\Feature;

use App\Support\Uploads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploads_are_saved_in_storage_and_deleted_from_it(): void
    {
        $name = Uploads::store(UploadedFile::fake()->image('new.jpg'), 'pitches', 'new-pitch.jpg');
        Storage::disk('public')->assertExists('pitches/new-pitch.jpg');

        $url = Uploads::url('pitches', $name);
        $this->assertTrue(str_contains($url, '/storage/pitches/new-pitch.jpg') || str_contains($url, '/media/pitches/new-pitch.jpg'));

        Uploads::delete('pitches', $name);
        Storage::disk('public')->assertMissing('pitches/new-pitch.jpg');

        // no picture: default avatar
        $this->assertStringEndsWith(Uploads::DEFAULT_AVATAR, Uploads::url('profile', null, Uploads::DEFAULT_AVATAR));
    }

    public function test_media_route_serves_storage_files_without_storage_link(): void
    {
        Storage::disk('public')->put('post/photo.jpg', 'image-bytes');

        $this->get('/media/post/photo.jpg')->assertOk();
        $this->get('/media/post/missing.jpg')->assertNotFound();
        $this->get('/media/secret/photo.jpg')->assertNotFound();
    }
}
