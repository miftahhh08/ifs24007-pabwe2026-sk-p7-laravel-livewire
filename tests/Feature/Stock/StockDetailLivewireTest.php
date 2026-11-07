<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Livewire\StockDetail;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StockDetailLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->member = User::factory()->member()->create();
        $this->actingAs($this->member);
    }

    private function stock(array $attributes = []): Stock
    {
        return Stock::factory()->create(array_merge(['user_id' => $this->member->id], $attributes));
    }

    public function test_renders_stock_and_markdown_description(): void
    {
        $stock = $this->stock(['name' => 'Beras Premium', 'description' => 'Ini **tebal**']);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->assertOk()
            ->assertSee('Beras Premium')
            ->assertSeeHtml('<strong>tebal</strong>');
    }

    public function test_markdown_html_is_stripped(): void
    {
        $stock = $this->stock(['description' => '<script>alert(1)</script> aman']);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->assertDontSeeHtml('<script>alert(1)</script>');
    }

    public function test_cannot_mount_other_users_stock(): void
    {
        $foreign = Stock::factory()->create();

        $this->expectException(AuthorizationException::class);
        $this->withoutExceptionHandling();

        Livewire::test(StockDetail::class, ['stock' => $foreign]);
    }

    public function test_edit_loads_values_and_cancel_closes(): void
    {
        $stock = $this->stock(['name' => 'Lama', 'sku' => null, 'description' => null, 'price' => null, 'quantity' => 9, 'min_stock' => 2]);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('edit')
            ->assertSet('editing', true)
            ->assertSet('name', 'Lama')
            ->assertSet('sku', '')
            ->assertSet('description', '')
            ->assertSet('price', '')
            ->assertSet('quantity', 9)
            ->assertSet('min_stock', 2)
            ->call('cancel')
            ->assertSet('editing', false);
    }

    public function test_edit_loads_existing_price(): void
    {
        $stock = $this->stock(['price' => 7500]);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('edit')
            ->assertSet('price', '7500.00');
    }

    public function test_save_validation_errors(): void
    {
        $stock = $this->stock();

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('edit')
            ->set('name', '')
            ->set('quantity', -3)
            ->call('save')
            ->assertHasErrors(['name', 'quantity'])
            ->assertSet('editing', true);
    }

    public function test_save_updates_stock_and_status(): void
    {
        $stock = $this->stock(['name' => 'Lama', 'quantity' => 50, 'min_stock' => 5]);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('edit')
            ->set('name', 'Baru')
            ->set('description', '## Rincian')
            ->set('quantity', 2)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editing', false)
            ->assertSee('Baru');

        $this->assertDatabaseHas('stocks', [
            'id' => $stock->id, 'name' => 'Baru', 'description' => '## Rincian', 'status' => StockStatus::LowStock->value,
        ]);
    }

    public function test_adjust_quantity(): void
    {
        $stock = $this->stock(['quantity' => 5, 'min_stock' => 5, 'status' => 'low_stock']);

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('adjust', 10)
            ->call('adjust', -15)
            ->call('adjust', -1);

        $fresh = $stock->fresh();
        $this->assertSame(0, $fresh->quantity);
        $this->assertSame(StockStatus::OutOfStock, $fresh->status);
    }

    public function test_cover_upload_validation(): void
    {
        $stock = $this->stock();

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('saveCover')
            ->assertHasErrors(['cover' => 'required'])
            ->set('cover', UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'))
            ->call('saveCover')
            ->assertHasErrors(['cover']);

        $this->assertNull($stock->fresh()->cover_image_path);
    }

    public function test_cover_upload_and_remove(): void
    {
        $stock = $this->stock(['cover_image_path' => null]);

        $component = Livewire::test(StockDetail::class, ['stock' => $stock])
            ->set('cover', UploadedFile::fake()->image('cover.png', 800, 400))
            ->call('saveCover')
            ->assertHasNoErrors()
            ->assertSet('cover', null);

        $path = $stock->fresh()->cover_image_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $component->call('removeCover');

        $this->assertNull($stock->fresh()->cover_image_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_delete_redirects_to_list_and_removes_stock(): void
    {
        $stock = $this->stock();

        Livewire::test(StockDetail::class, ['stock' => $stock])
            ->call('delete')
            ->assertRedirect('/stocks');

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('actions')]
    public function test_ownership_is_enforced_on_every_action_if_stock_changes_hands(string $action, array $args): void
    {
        $stock = $this->stock();
        $component = Livewire::test(StockDetail::class, ['stock' => $stock]);

        // Setelah halaman dimuat, kepemilikan berpindah: setiap aksi harus ditolak.
        $stock->update(['user_id' => User::factory()->create()->id]);

        $component->call($action, ...$args)->assertForbidden();
    }

    /**
     * @return array<string, array{string, array<int, mixed>}>
     */
    public static function actions(): array
    {
        return [
            'edit' => ['edit', []],
            'save' => ['save', []],
            'adjust' => ['adjust', [1]],
            'saveCover' => ['saveCover', []],
            'removeCover' => ['removeCover', []],
            'delete' => ['delete', []],
        ];
    }
}
