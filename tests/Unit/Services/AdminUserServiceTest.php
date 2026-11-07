<?php

namespace Tests\Unit\Services;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminUserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AdminUserService::class);
    }

    public function test_paginate_users_includes_stock_count(): void
    {
        $member = User::factory()->create();
        Stock::factory()->count(3)->create(['user_id' => $member->id]);

        $page = $this->service->paginateUsers(null);

        $this->assertSame(3, $page->firstWhere('id', $member->id)->stocks_count);
    }

    public function test_paginate_users_search_by_name_username_or_email(): void
    {
        User::factory()->create(['name' => 'Budi Santoso', 'username' => 'budi', 'email' => 'budi@x.test']);
        User::factory()->create(['name' => 'Sari', 'username' => 'sari_k', 'email' => 'sari@x.test']);
        User::factory()->create(['name' => 'Joko', 'username' => 'joko', 'email' => 'joko@kantor.test']);

        $this->assertSame(1, $this->service->paginateUsers('Santoso')->total());
        $this->assertSame(1, $this->service->paginateUsers('sari_k')->total());
        $this->assertSame(1, $this->service->paginateUsers('kantor')->total());
        $this->assertSame(3, $this->service->paginateUsers(null)->total());
    }

    public function test_paginate_stocks_of_user_scopes_search_and_status(): void
    {
        $member = User::factory()->create();
        Stock::factory()->create(['user_id' => $member->id, 'name' => 'Kabel USB', 'quantity' => 0, 'status' => 'out_of_stock']);
        Stock::factory()->create(['user_id' => $member->id, 'name' => 'Mouse', 'quantity' => 40, 'status' => 'available']);
        Stock::factory()->create(['name' => 'Kabel Milik Orang Lain']);

        $this->assertSame(2, $this->service->paginateStocksOf($member, null, null)->total());
        $this->assertSame(1, $this->service->paginateStocksOf($member, 'Kabel', null)->total());
        $this->assertSame(1, $this->service->paginateStocksOf($member, null, 'available')->total());
    }
}
