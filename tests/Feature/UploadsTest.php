<?php

namespace Tests\Feature;

use App\Support\Uploads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadsTest extends TestCase
{
    use RefreshDatabase, CreatesSportClubData;

    private string $legacyFile;

    private string $legacyRoot;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Never touch the real public/images folder in tests: use a temporary one.
        $this->legacyRoot = storage_path('framework/testing/legacy-'.uniqid());
        config(['sportclub.legacy_uploads_path' => $this->legacyRoot]);
        File::ensureDirectoryExists($this->legacyRoot.'/pitches');
        $this->legacyFile = 'legacy-test.jpg';
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->legacyRoot);
        parent::tearDown();
    }

    public function test_new_uploads_go_to_storage_and_old_ones_still_show(): void
    {
        // an old upload, still in public/images
        File::put($this->legacyRoot.'/pitches/'.$this->legacyFile, 'old');
        $this->assertStringEndsWith('/images/pitches/'.$this->legacyFile, Uploads::url('pitches', $this->legacyFile));

        // a new upload
        $name = Uploads::store(UploadedFile::fake()->image('new.jpg'), 'pitches', 'new-pitch.jpg');
        Storage::disk('public')->assertExists('pitches/new-pitch.jpg');
        $url = Uploads::url('pitches', $name);
        $this->assertTrue(str_contains($url, '/storage/pitches/new-pitch.jpg') || str_contains($url, '/media/pitches/new-pitch.jpg'));

        // no picture → default avatar
        $this->assertStringEndsWith(Uploads::DEFAULT_AVATAR, Uploads::url('profile', null, Uploads::DEFAULT_AVATAR));
    }

    public function test_media_route_serves_storage_files_without_storage_link(): void
    {
        Storage::disk('public')->put('post/photo.jpg', 'image-bytes');

        $this->get('/media/post/photo.jpg')->assertOk();
        $this->get('/media/post/missing.jpg')->assertNotFound();
        $this->get('/media/secret/photo.jpg')->assertNotFound();
    }

    public function test_move_uploads_command_moves_old_files(): void
    {
        File::put($this->legacyRoot.'/pitches/'.$this->legacyFile, 'old-bytes');

        $this->artisan('sportclub:move-uploads')->assertSuccessful();

        Storage::disk('public')->assertExists('pitches/'.$this->legacyFile);
        $this->assertSame('old-bytes', Storage::disk('public')->get('pitches/'.$this->legacyFile));
        $this->assertFileDoesNotExist($this->legacyRoot.'/pitches/'.$this->legacyFile);
    }
}
