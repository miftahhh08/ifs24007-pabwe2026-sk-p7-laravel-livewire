<?php

namespace Tests\Unit\Repositories;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private StockRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new StockRepository;
    }

    public function test_paginate_only_returns_stocks_of_user(): void
    {
        $user = User::factory()->create();
        Stock::factory()->count(3)->create(['user_id' => $user->id]);
        Stock::factory()->count(2)->create();

        $page = $this->repo->paginateForUser($user->id, null, null);

        $this->assertSame(3, $page->total());
    }

    public function test_paginate_search_matches_name_or_sku(): void
    {
        $user = User::factory()->create();
        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Beras Pandan', 'sku' => 'AAA-1']);
        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Gula', 'sku' => 'BERAS-9']);
        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Kopi', 'sku' => 'KPI-1']);

        $page = $this->repo->paginateForUser($user->id, 'beras', null);

        $this->assertSame(2, $page->total());
    }

    public function test_paginate_filters_by_status_and_orders_newest_first(): void
    {
        $user = User::factory()->create();
        $a = Stock::factory()->create(['user_id' => $user->id, 'quantity' => 0, 'status' => 'out_of_stock']);
        Stock::factory()->create(['user_id' => $user->id, 'quantity' => 50, 'status' => 'available']);
        $c = Stock::factory()->create(['user_id' => $user->id, 'quantity' => 0, 'status' => 'out_of_stock']);

        $page = $this->repo->paginateForUser($user->id, null, 'out_of_stock');

        $this->assertSame([$c->id, $a->id], $page->pluck('id')->all());
    }

    public function test_paginate_respects_per_page(): void
    {
        $user = User::factory()->create();
        Stock::factory()->count(5)->create(['user_id' => $user->id]);

        $page = $this->repo->paginateForUser($user->id, null, null, 2);

        $this->assertCount(2, $page->items());
        $this->assertSame(3, $page->lastPage());
    }

    public function test_all_for_user_is_ordered_by_id(): void
    {
        $user = User::factory()->create();
        $first = Stock::factory()->create(['user_id' => $user->id]);
        $second = Stock::factory()->create(['user_id' => $user->id]);
        Stock::factory()->create();

        $this->assertSame([$first->id, $second->id], $this->repo->allForUser($user->id)->pluck('id')->all());
    }

    public function test_find_or_fail(): void
    {
        $stock = Stock::factory()->create();

        $this->assertTrue($this->repo->findOrFail($stock->id)->is($stock));

        $this->expectException(ModelNotFoundException::class);
        $this->repo->findOrFail(999999);
    }

    public function test_create_update_delete(): void
    {
        $user = User::factory()->create();

        $stock = $this->repo->create([
            'user_id' => $user->id, 'name' => 'Pensil', 'quantity' => 10, 'unit' => 'pcs',
            'min_stock' => 5, 'status' => 'available',
        ]);
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'name' => 'Pensil']);

        $updated = $this->repo->update($stock, ['name' => 'Pensil 2B']);
        $this->assertSame('Pensil 2B', $updated->name);

        $this->repo->delete($stock);
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }
}
