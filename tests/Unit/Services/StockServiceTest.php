<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = app(StockService::class);
    }

    public function test_rules_cover_all_form_fields(): void
    {
        $this->assertSame(
            ['name', 'sku', 'description', 'quantity', 'unit', 'price', 'min_stock'],
            array_keys(StockService::rules())
        );
    }

    public function test_list_and_all_are_scoped_to_user(): void
    {
        $user = User::factory()->create();
        Stock::factory()->count(2)->create(['user_id' => $user->id]);
        Stock::factory()->create();

        $this->assertSame(2, $this->service->list($user, null, null)->total());
        $this->assertCount(2, $this->service->all($user));
    }

    public function test_find(): void
    {
        $stock = Stock::factory()->create();

        $this->assertTrue($this->service->find($stock->id)->is($stock));
    }

    public function test_create_assigns_owner_normalizes_blanks_and_computes_status(): void
    {
        $user = User::factory()->create();

        $stock = $this->service->create($user, [
            'name' => 'Lampu', 'sku' => '', 'description' => null, 'price' => '',
            'quantity' => '3', 'unit' => 'pcs', 'min_stock' => '5',
        ]);

        $this->assertSame($user->id, $stock->user_id);
        $this->assertNull($stock->sku);
        $this->assertNull($stock->description);
        $this->assertNull($stock->price);
        $this->assertSame(3, $stock->quantity);
        $this->assertSame(StockStatus::LowStock, $stock->status);
    }

    public function test_update_recomputes_status(): void
    {
        $stock = Stock::factory()->create(['quantity' => 50, 'min_stock' => 5, 'status' => 'available']);

        $updated = $this->service->update($stock, [
            'name' => 'Baru', 'quantity' => 0, 'unit' => 'pcs', 'min_stock' => 5, 'price' => 2500,
        ]);

        $this->assertSame('Baru', $updated->name);
        $this->assertSame(StockStatus::OutOfStock, $updated->status);
    }

    public function test_adjust_quantity_updates_status_both_directions(): void
    {
        $stock = Stock::factory()->create(['quantity' => 6, 'min_stock' => 5, 'status' => 'available']);

        $stock = $this->service->adjustQuantity($stock, -1);
        $this->assertSame(5, $stock->quantity);
        $this->assertSame(StockStatus::LowStock, $stock->status);

        $stock = $this->service->adjustQuantity($stock, -5);
        $this->assertSame(0, $stock->quantity);
        $this->assertSame(StockStatus::OutOfStock, $stock->status);

        $stock = $this->service->adjustQuantity($stock, 10);
        $this->assertSame(10, $stock->quantity);
        $this->assertSame(StockStatus::Available, $stock->status);
    }

    public function test_adjust_quantity_never_goes_below_zero(): void
    {
        $stock = Stock::factory()->create(['quantity' => 2, 'min_stock' => 5, 'status' => 'low_stock']);

        $stock = $this->service->adjustQuantity($stock, -100);

        $this->assertSame(0, $stock->quantity);
        $this->assertSame(StockStatus::OutOfStock, $stock->status);
    }

    public function test_upload_cover_replaces_previous_file(): void
    {
        $stock = Stock::factory()->create();

        $stock = $this->service->uploadCover($stock, UploadedFile::fake()->image('a.png'));
        $first = $stock->cover_image_path;
        Storage::disk('public')->assertExists($first);

        $stock = $this->service->uploadCover($stock, UploadedFile::fake()->image('b.png'));

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($stock->cover_image_path);
    }

    public function test_remove_cover(): void
    {
        $stock = $this->service->uploadCover(Stock::factory()->create(), UploadedFile::fake()->image('a.png'));
        $path = $stock->cover_image_path;

        $stock = $this->service->removeCover($stock);

        $this->assertNull($stock->cover_image_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_delete_removes_record_and_cover_file(): void
    {
        $stock = $this->service->uploadCover(Stock::factory()->create(), UploadedFile::fake()->image('a.png'));
        $path = $stock->cover_image_path;

        $this->service->delete($stock);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_delete_without_cover(): void
    {
        $stock = Stock::factory()->create(['cover_image_path' => null]);

        $this->service->delete($stock);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }
}
