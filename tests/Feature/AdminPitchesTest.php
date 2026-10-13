<?php

namespace Tests\Feature;

use App\Models\Pitche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminPitchesTest extends TestCase
{
    use RefreshDatabase, CreatesSportClubData;

    public function test_admin_adds_and_edits_a_pitch(): void
    {
        $admin = $this->makeUser('admin');
        $provider = $this->makeUser('provider');

        $this->actingAs($admin)->post('/AdminDashbord', [
            'name' => 'Stade Yasmine',
            'provider' => $provider->id,
            'capacity' => 10,
            'localisation' => 'Khemisset',
            'openhours' => '9:00-22:00',
            'image' => UploadedFile::fake()->image('pitch.jpg'),
        ])->assertRedirect(route('AdminDashbord.index', ['tab' => 'pitches']));

        $pitch = Pitche::where('name', 'Stade Yasmine')->firstOrFail();
        $this->assertSame([9, 22], $pitch->openingRange());
        $this->assertFileExists(public_path('images/pitches/'.$pitch->image));

        $this->actingAs($admin)->get('/AdminDashbord/'.$pitch->id.'/edit')->assertOk();
        $this->actingAs($admin)->put('/AdminDashbord/'.$pitch->id, [
            'name' => 'Stade Yasmine ',
            'provider' => $provider->id,
            'capacity' => 14,
            'openhours' => '10:00-20:00',
        ])->assertRedirect();

        $pitch->refresh();
        $this->assertSame('Stade Yasmine', $pitch->name, 'name is trimmed so its URL keeps working');
        $this->assertSame(14, (int) $pitch->capacity);
        $this->assertSame('10:00-20:00', $pitch->openhours);

        $this->actingAs($admin)->delete('/AdminDashbord/'.$pitch->id)->assertRedirect();
        $this->assertDatabaseMissing('pitches', ['id' => $pitch->id]);
        $this->assertFileDoesNotExist(public_path('images/pitches/'.$pitch->image));
    }

    public function test_pitch_form_validation(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post('/AdminDashbord', [
            'name' => '', 'provider' => 999, 'capacity' => 'many', 'openhours' => 'all day',
        ])->assertSessionHasErrors(['name', 'provider', 'capacity', 'localisation', 'openhours', 'image']);
    }
}
