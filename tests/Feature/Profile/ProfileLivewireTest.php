<?php

namespace Tests\Feature\Profile;

use App\Features\Profile\Livewire\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create([
            'name' => 'Budi', 'username' => 'budi', 'email' => 'budi@example.test', 'password' => 'lama12345',
        ]);
        $this->actingAs($this->user);
    }

    public function test_mount_prefills_form(): void
    {
        Livewire::test(Profile::class)
            ->assertSet('name', 'Budi')
            ->assertSet('username', 'budi')
            ->assertSet('email', 'budi@example.test');
    }

    public function test_save_profile(): void
    {
        Livewire::test(Profile::class)
            ->set('name', 'Budi Baru')
            ->set('username', 'budi_baru')
            ->set('email', 'baru@example.test')
            ->call('saveProfile')
            ->assertHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'name' => 'Budi Baru', 'username' => 'budi_baru', 'email' => 'baru@example.test']);
    }

    public function test_save_profile_allows_keeping_own_username_and_email(): void
    {
        Livewire::test(Profile::class)
            ->set('name', 'Hanya Nama')
            ->call('saveProfile')
            ->assertHasNoErrors();
    }

    public function test_save_profile_validation_and_uniqueness(): void
    {
        User::factory()->create(['username' => 'dipakai', 'email' => 'dipakai@example.test']);

        Livewire::test(Profile::class)
            ->set('name', '')
            ->set('username', 'dipakai')
            ->set('email', 'dipakai@example.test')
            ->call('saveProfile')
            ->assertHasErrors(['name', 'username' => 'unique', 'email' => 'unique']);

        Livewire::test(Profile::class)
            ->set('username', 'ada spasi')
            ->set('email', 'bukan-email')
            ->call('saveProfile')
            ->assertHasErrors(['username', 'email']);
    }

    public function test_change_password(): void
    {
        Livewire::test(Profile::class)
            ->set('current_password', 'lama12345')
            ->set('password', 'baru12345')
            ->set('password_confirmation', 'baru12345')
            ->call('savePassword')
            ->assertHasNoErrors()
            ->assertSet('current_password', '')
            ->assertSet('password', '')
            ->assertSet('password_confirmation', '');

        $this->assertTrue(Hash::check('baru12345', $this->user->fresh()->password));
    }

    public function test_change_password_validation(): void
    {
        Livewire::test(Profile::class)
            ->set('current_password', 'salah')
            ->set('password', 'pendek')
            ->set('password_confirmation', 'beda')
            ->call('savePassword')
            ->assertHasErrors(['current_password', 'password']);

        $this->assertTrue(Hash::check('lama12345', $this->user->fresh()->password));
    }

    public function test_photo_upload_validation(): void
    {
        Livewire::test(Profile::class)
            ->call('savePhoto')
            ->assertHasErrors(['photo' => 'required'])
            ->set('photo', UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf'))
            ->call('savePhoto')
            ->assertHasErrors(['photo']);
    }

    public function test_photo_upload_and_remove(): void
    {
        Livewire::test(Profile::class)
            ->set('photo', UploadedFile::fake()->image('foto.png', 300, 300))
            ->call('savePhoto')
            ->assertHasNoErrors()
            ->assertRedirect('/profile');

        $path = $this->user->fresh()->profile_photo_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        Livewire::test(Profile::class)
            ->call('removePhoto')
            ->assertRedirect('/profile');

        $this->assertNull($this->user->fresh()->profile_photo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_page_requires_login(): void
    {
        auth()->logout();

        $this->get('/profile')->assertRedirect(route('login'));
    }
}
