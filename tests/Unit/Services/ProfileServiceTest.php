<?php

namespace Tests\Unit\Services;

use App\Features\Profile\Services\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = new ProfileService;
    }

    public function test_update_profile(): void
    {
        $user = User::factory()->create();

        $result = $this->service->updateProfile($user, ['name' => 'Nama Baru', 'username' => 'baru', 'email' => 'baru@x.test']);

        $this->assertSame('Nama Baru', $result->name);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => 'baru', 'email' => 'baru@x.test']);
    }

    public function test_update_password_is_hashed(): void
    {
        $user = User::factory()->create();

        $this->service->updatePassword($user, 'password-baru');

        $this->assertTrue(Hash::check('password-baru', $user->fresh()->password));
    }

    public function test_update_photo_stores_file_and_replaces_old_one(): void
    {
        $user = User::factory()->create();

        $first = $this->service->updatePhoto($user, UploadedFile::fake()->image('a.png'));
        $firstPath = $first->profile_photo_path;
        Storage::disk('public')->assertExists($firstPath);

        $second = $this->service->updatePhoto($user, UploadedFile::fake()->image('b.png'));

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($second->profile_photo_path);
    }

    public function test_remove_photo_deletes_file(): void
    {
        $user = User::factory()->create();
        $user = $this->service->updatePhoto($user, UploadedFile::fake()->image('a.png'));
        $path = $user->profile_photo_path;

        $result = $this->service->removePhoto($user);

        $this->assertNull($result->profile_photo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_remove_photo_without_photo_is_safe(): void
    {
        $user = User::factory()->create(['profile_photo_path' => null]);

        $this->assertNull($this->service->removePhoto($user)->profile_photo_path);
    }
}
