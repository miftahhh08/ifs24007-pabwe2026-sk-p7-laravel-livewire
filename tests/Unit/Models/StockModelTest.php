<?php

namespace Tests\Unit\Models;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts(): void
    {
        $stock = Stock::factory()->create([
            'quantity' => '7',
            'min_stock' => '2',
            'price' => 1500,
            'status' => 'available',
        ])->refresh();

        $this->assertSame(7, $stock->quantity);
        $this->assertSame(2, $stock->min_stock);
        $this->assertSame('1500.00', $stock->price);
        $this->assertSame(StockStatus::Available, $stock->status);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $stock = Stock::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($stock->user->is($user));
    }

    public function test_sync_status_follows_quantity_and_min_stock(): void
    {
        $stock = Stock::factory()->make(['quantity' => 50, 'min_stock' => 5]);

        $stock->syncStatus();
        $this->assertSame(StockStatus::Available, $stock->status);

        $stock->quantity = 5;
        $stock->syncStatus();
        $this->assertSame(StockStatus::LowStock, $stock->status);

        $stock->quantity = 0;
        $stock->syncStatus();
        $this->assertSame(StockStatus::OutOfStock, $stock->status);
    }

    public function test_cover_url(): void
    {
        Storage::fake('public');

        $stock = Stock::factory()->make(['cover_image_path' => null]);
        $this->assertNull($stock->coverUrl());

        $stock->cover_image_path = 'stock-covers/x.jpg';
        $this->assertStringContainsString('stock-covers/x.jpg', (string) $stock->coverUrl());
    }

    public function test_factory_derives_status_from_quantity(): void
    {
        $this->assertSame(StockStatus::OutOfStock, Stock::factory()->make(['quantity' => 0])->status);
        $this->assertSame(StockStatus::LowStock, Stock::factory()->make(['quantity' => 3, 'min_stock' => 5])->status);
        $this->assertSame(StockStatus::Available, Stock::factory()->make(['quantity' => 30, 'min_stock' => 5])->status);
    }
}
