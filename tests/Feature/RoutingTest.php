<?php

namespace Tests\Feature;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_guest_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_root_redirects_member_to_stocks(): void
    {
        $this->actingAs(User::factory()->member()->create())
            ->get('/')
            ->assertRedirect('/stocks');
    }

    public function test_root_redirects_admin_to_admin_users(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/')
            ->assertRedirect('/admin/users');
    }

    public function test_health_check(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_guest_is_redirected_to_login_on_protected_routes(): void
    {
        $stock = Stock::factory()->create();
        $user = User::factory()->create();

        foreach (['/stocks', "/stocks/{$stock->id}", '/profile', '/admin/users', "/admin/users/{$user->id}/stocks"] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_guest_cannot_logout(): void
    {
        $this->post('/logout')->assertRedirect(route('login'));
    }

    public function test_member_pages_render(): void
    {
        $member = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $member->id]);

        $this->actingAs($member);
        $this->get('/stocks')->assertOk();
        $this->get("/stocks/{$stock->id}")->assertOk();
        $this->get('/profile')->assertOk();
    }

    public function test_member_cannot_open_admin_routes(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member);
        $this->get('/admin/users')->assertForbidden();
        $this->get("/admin/users/{$member->id}/stocks")->assertForbidden();
    }

    public function test_admin_cannot_open_member_stock_routes(): void
    {
        $member = User::factory()->member()->create();
        $stock = Stock::factory()->create(['user_id' => $member->id]);

        $this->actingAs(User::factory()->admin()->create());
        $this->get('/stocks')->assertForbidden();
        $this->get("/stocks/{$stock->id}")->assertForbidden();
    }

    public function test_member_cannot_open_other_members_stock(): void
    {
        $stock = Stock::factory()->create();

        $this->actingAs(User::factory()->member()->create())
            ->get("/stocks/{$stock->id}")
            ->assertForbidden();
    }

    public function test_unknown_stock_is_404_for_member(): void
    {
        $this->actingAs(User::factory()->member()->create())
            ->get('/stocks/999999')
            ->assertNotFound();
    }

    public function test_admin_pages_render(): void
    {
        $member = User::factory()->member()->create();
        Stock::factory()->create(['user_id' => $member->id, 'name' => 'Barang Member']);

        $this->actingAs(User::factory()->admin()->create());
        $this->get('/admin/users')->assertOk()->assertSee($member->username);
        $this->get("/admin/users/{$member->id}/stocks")->assertOk()->assertSee('Barang Member');
    }
}
