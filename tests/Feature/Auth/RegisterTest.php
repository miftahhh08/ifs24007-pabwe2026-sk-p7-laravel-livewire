<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_renders(): void
    {
        $this->get('/register')->assertOk()->assertSeeLivewire(Register::class);
    }

    public function test_registration_creates_member_and_redirects_to_login(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Budi Santoso')
            ->set('username', 'budi_s')
            ->set('email', 'budi@example.test')
            ->set('password', 'rahasia123')
            ->set('password_confirmation', 'rahasia123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect('/login');

        $user = User::query()->where('username', 'budi_s')->firstOrFail();
        $this->assertSame('member', $user->role);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertGuest();
    }

    public function test_cannot_register_as_admin_by_tampering(): void
    {
        $component = Livewire::test(Register::class)
            ->set('name', 'X')
            ->set('username', 'xx')
            ->set('email', 'x@example.test')
            ->set('password', 'rahasia123')
            ->set('password_confirmation', 'rahasia123');

        $component->call('register');

        $this->assertSame('member', User::query()->where('username', 'xx')->value('role'));
    }

    public function test_validation_errors(): void
    {
        Livewire::test(Register::class)
            ->set('name', '')
            ->set('username', 'spasi tidak boleh')
            ->set('email', 'bukan-email')
            ->set('password', 'pendek')
            ->set('password_confirmation', 'beda')
            ->call('register')
            ->assertHasErrors(['name', 'username', 'email', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_username_and_email_must_be_unique(): void
    {
        User::factory()->create(['username' => 'budi', 'email' => 'budi@example.test']);

        Livewire::test(Register::class)
            ->set('name', 'Lain')
            ->set('username', 'budi')
            ->set('email', 'budi@example.test')
            ->set('password', 'rahasia123')
            ->set('password_confirmation', 'rahasia123')
            ->call('register')
            ->assertHasErrors(['username' => 'unique', 'email' => 'unique']);
    }
}
