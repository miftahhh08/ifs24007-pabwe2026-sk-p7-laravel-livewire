<?php

namespace Tests\Unit\Models;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_helpers(): void
    {
        $admin = User::factory()->admin()->make();
        $member = User::factory()->member()->make();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isMember());
        $this->assertTrue($member->isMember());
        $this->assertFalse($member->isAdmin());
    }

    public function test_password_is_hashed_and_hidden(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->assertNotSame('rahasia123', $user->password);
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_stocks_relation(): void
    {
        $user = User::factory()->create();
        Stock::factory()->count(2)->create(['user_id' => $user->id]);
        Stock::factory()->create();

        $this->assertCount(2, $user->stocks);
    }

    public function test_profile_photo_url(): void
    {
        Storage::fake('public');

        $user = User::factory()->make(['profile_photo_path' => null]);
        $this->assertNull($user->profilePhotoUrl());

        $user->profile_photo_path = 'profile-photos/a.jpg';
        $this->assertStringContainsString('profile-photos/a.jpg', (string) $user->profilePhotoUrl());
    }

    public function test_deleting_user_cascades_to_stocks(): void
    {
        $user = User::factory()->create();
        Stock::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseCount('stocks', 0);
    }
}
