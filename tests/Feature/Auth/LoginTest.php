<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_has_required_element_ids(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSeeLivewire(Login::class)
            ->assertSee('id="login-username"', false)
            ->assertSee('id="login-password"', false)
            ->assertSee('id="login-submit"', false);
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect();
    }

    public function test_member_logs_in_with_username_and_goes_to_stocks(): void
    {
        User::factory()->create(['username' => 'budi', 'password' => 'rahasia123']);

        Livewire::test(Login::class)
            ->set('login', 'budi')
            ->set('password', 'rahasia123')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect('/stocks');

        $this->assertAuthenticated();
    }

    public function test_user_logs_in_with_email(): void
    {
        User::factory()->create(['email' => 'budi@example.test', 'password' => 'rahasia123']);

        Livewire::test(Login::class)
            ->set('login', 'budi@example.test')
            ->set('password', 'rahasia123')
            ->call('authenticate')
            ->assertRedirect('/stocks');

        $this->assertAuthenticated();
    }

    public function test_admin_goes_to_admin_users(): void
    {
        User::factory()->admin()->create(['username' => 'admin', 'password' => 'admin']);

        Livewire::test(Login::class)
            ->set('login', 'admin')
            ->set('password', 'admin')
            ->call('authenticate')
            ->assertRedirect('/admin/users');
    }

    public function test_wrong_password_shows_error_and_does_not_authenticate(): void
    {
        User::factory()->create(['username' => 'budi', 'password' => 'rahasia123']);

        Livewire::test(Login::class)
            ->set('login', 'budi')
            ->set('password', 'salah')
            ->call('authenticate')
            ->assertHasErrors(['login'])
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_unknown_user_is_rejected(): void
    {
        Livewire::test(Login::class)
            ->set('login', 'tidak-ada')
            ->set('password', 'apa-saja')
            ->call('authenticate')
            ->assertHasErrors(['login']);

        $this->assertGuest();
    }

    public function test_fields_are_required(): void
    {
        Livewire::test(Login::class)
            ->call('authenticate')
            ->assertHasErrors(['login' => 'required', 'password' => 'required']);
    }

    public function test_logout_clears_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
