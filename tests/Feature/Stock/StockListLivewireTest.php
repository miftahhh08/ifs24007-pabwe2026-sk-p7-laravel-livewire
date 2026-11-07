<?php

namespace Tests\Feature\Stock;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StockListLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::factory()->member()->create();
        $this->actingAs($this->member);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function fillForm($component, array $overrides = [])
    {
        $values = array_merge([
            'name' => 'Beras 5kg', 'sku' => 'BRS-1', 'description' => 'Beras **putih**',
            'quantity' => 20, 'unit' => 'karung', 'price' => '65000', 'min_stock' => 5,
        ], $overrides);

        foreach ($values as $key => $value) {
            $component->set($key, $value);
        }

        return $component;
    }

    // ---------------------------------------------------------------- list

    public function test_shows_only_own_stocks(): void
    {
        Stock::factory()->create(['user_id' => $this->member->id, 'name' => 'Stok Milikku']);
        Stock::factory()->create(['name' => 'Stok Orang Lain']);

        Livewire::test(StockList::class)
            ->assertSee('Stok Milikku')
            ->assertDontSee('Stok Orang Lain');
    }

    public function test_search_by_name_and_sku_and_status_filter(): void
    {
        Stock::factory()->create(['user_id' => $this->member->id, 'name' => 'Kabel USB', 'sku' => 'KBL-1', 'quantity' => 0, 'status' => 'out_of_stock']);
        Stock::factory()->create(['user_id' => $this->member->id, 'name' => 'Mouse Gaming', 'sku' => 'MSE-1', 'quantity' => 40, 'status' => 'available']);

        Livewire::test(StockList::class)
            ->set('search', 'Kabel')
            ->assertSee('Kabel USB')
            ->assertDontSee('Mouse Gaming')
            ->set('search', 'MSE-1')
            ->assertSee('Mouse Gaming')
            ->assertDontSee('Kabel USB')
            ->set('search', '')
            ->set('statusFilter', 'out_of_stock')
            ->assertSee('Kabel USB')
            ->assertDontSee('Mouse Gaming');
    }

    public function test_pagination_resets_when_filter_changes(): void
    {
        Stock::factory()->count(12)->create(['user_id' => $this->member->id]);

        $component = Livewire::test(StockList::class)->call('setPage', 2);
        $this->assertSame(2, $component->instance()->getPage());

        $component->set('search', 'x');
        $this->assertSame(1, $component->instance()->getPage());

        $component->set('search', '')->call('setPage', 2)->set('statusFilter', 'available');
        $this->assertSame(1, $component->instance()->getPage());
    }

    // ---------------------------------------------------------------- CRUD

    public function test_create_validation_errors(): void
    {
        $component = Livewire::test(StockList::class)->call('openCreate')->assertSet('showForm', true);

        $this->fillForm($component, ['name' => '', 'quantity' => -1, 'unit' => '', 'price' => 'mahal', 'min_stock' => 'x'])
            ->call('save')
            ->assertHasErrors(['name', 'quantity', 'unit', 'price', 'min_stock']);

        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_create_stock_computes_status_and_closes_form(): void
    {
        $component = Livewire::test(StockList::class)->call('openCreate');

        $this->fillForm($component, ['quantity' => 3, 'min_stock' => 5])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('stocks', [
            'user_id' => $this->member->id,
            'name' => 'Beras 5kg',
            'sku' => 'BRS-1',
            'quantity' => 3,
            'status' => StockStatus::LowStock->value,
        ]);
    }

    public function test_open_create_resets_previous_form_values(): void
    {
        $stock = Stock::factory()->create(['user_id' => $this->member->id, 'name' => 'Lama']);

        Livewire::test(StockList::class)
            ->call('openEdit', $stock->id)
            ->assertSet('name', 'Lama')
            ->call('openCreate')
            ->assertSet('name', '')
            ->assertSet('editingId', null)
            ->assertSet('unit', 'pcs');
    }

    public function test_edit_stock(): void
    {
        $stock = Stock::factory()->create([
            'user_id' => $this->member->id, 'name' => 'Nama Lama', 'price' => null, 'quantity' => 50, 'min_stock' => 5,
        ]);

        Livewire::test(StockList::class)
            ->call('openEdit', $stock->id)
            ->assertSet('editingId', $stock->id)
            ->assertSet('name', 'Nama Lama')
            ->assertSet('price', '')
            ->assertSet('showForm', true)
            ->set('name', 'Nama Baru')
            ->set('quantity', 0)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('stocks', [
            'id' => $stock->id, 'name' => 'Nama Baru', 'status' => StockStatus::OutOfStock->value,
        ]);
    }

    public function test_edit_loads_existing_price(): void
    {
        $stock = Stock::factory()->create(['user_id' => $this->member->id, 'price' => 1234.5]);

        Livewire::test(StockList::class)
            ->call('openEdit', $stock->id)
            ->assertSet('price', '1234.50');
    }

    public function test_close_form_resets_state(): void
    {
        Livewire::test(StockList::class)
            ->call('openCreate')
            ->set('name', 'Draft')
            ->call('closeForm')
            ->assertSet('showForm', false)
            ->assertSet('name', '');
    }

    public function test_delete_stock(): void
    {
        $stock = Stock::factory()->create(['user_id' => $this->member->id]);

        Livewire::test(StockList::class)->call('delete', $stock->id);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    // ---------------------------------------------------------- adjust qty

    public function test_adjust_quantity_updates_status(): void
    {
        $stock = Stock::factory()->create([
            'user_id' => $this->member->id, 'quantity' => 6, 'min_stock' => 5, 'status' => 'available',
        ]);

        $component = Livewire::test(StockList::class);

        $component->call('adjust', $stock->id, -1);
        $this->assertSame(5, $stock->fresh()->quantity);
        $this->assertSame(StockStatus::LowStock, $stock->fresh()->status);

        $component->call('adjust', $stock->id, -10);
        $this->assertSame(0, $stock->fresh()->quantity);
        $this->assertSame(StockStatus::OutOfStock, $stock->fresh()->status);

        $component->call('adjust', $stock->id, 20);
        $this->assertSame(20, $stock->fresh()->quantity);
        $this->assertSame(StockStatus::Available, $stock->fresh()->status);
    }

    // ------------------------------------------------------------ ownership

    public function test_cannot_edit_delete_or_adjust_other_users_stock(): void
    {
        $foreign = Stock::factory()->create(['name' => 'Bukan Milikku', 'quantity' => 10]);

        Livewire::test(StockList::class)->call('openEdit', $foreign->id)->assertForbidden();
        Livewire::test(StockList::class)->call('delete', $foreign->id)->assertForbidden();
        Livewire::test(StockList::class)->call('adjust', $foreign->id, 1)->assertForbidden();

        $this->assertDatabaseHas('stocks', ['id' => $foreign->id, 'name' => 'Bukan Milikku', 'quantity' => 10]);
    }

    public function test_cannot_save_over_other_users_stock_by_tampering_editing_id(): void
    {
        $foreign = Stock::factory()->create(['name' => 'Bukan Milikku']);

        $component = Livewire::test(StockList::class)->call('openCreate');
        $this->fillForm($component, ['name' => 'Dibajak'])
            ->set('editingId', $foreign->id)
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseHas('stocks', ['id' => $foreign->id, 'name' => 'Bukan Milikku']);
    }

    public function test_admin_is_denied_every_stock_action(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (['openCreate', 'openImport', 'openAi', 'export', 'import', 'generateAi', 'saveAi'] as $action) {
            Livewire::test(StockList::class)->call($action)->assertForbidden();
        }
    }

    // --------------------------------------------------------------- excel

    public function test_export_downloads_xlsx(): void
    {
        Stock::factory()->create(['user_id' => $this->member->id]);

        Livewire::test(StockList::class)
            ->call('export')
            ->assertFileDownloaded();
    }

    private function xlsxUpload(string $name = 'stok.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['name', 'sku', 'qty', 'unit', 'price', 'min_stock'],
            ['Impor Satu', 'IMP-1', 8, 'box', 1500, 5],
            ['Impor Dua', 'IMP-2', 0, 'pcs', null, 5],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'imp-');
        (new Xlsx($spreadsheet))->save($path);
        $content = (string) file_get_contents($path);
        @unlink($path);

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    public function test_import_excel(): void
    {
        Livewire::test(StockList::class)
            ->call('openImport')
            ->assertSet('showImport', true)
            ->set('importFile', $this->xlsxUpload())
            ->call('import')
            ->assertHasNoErrors()
            ->assertSet('showImport', false);

        $this->assertDatabaseHas('stocks', ['user_id' => $this->member->id, 'name' => 'Impor Satu', 'status' => 'available']);
        $this->assertDatabaseHas('stocks', ['user_id' => $this->member->id, 'name' => 'Impor Dua', 'status' => 'out_of_stock']);
    }

    public function test_import_requires_valid_file_type(): void
    {
        Livewire::test(StockList::class)
            ->call('import')
            ->assertHasErrors(['importFile' => 'required'])
            ->set('importFile', UploadedFile::fake()->create('gambar.png', 10, 'image/png'))
            ->call('import')
            ->assertHasErrors(['importFile']);
    }

    public function test_import_reports_unreadable_spreadsheet(): void
    {
        $this->app->instance(StockExcelService::class, new class(app(\App\Features\Stock\Services\StockService::class)) extends StockExcelService
        {
            public function import(User $user, string $path): int
            {
                throw new SpreadsheetException('rusak');
            }
        });

        Livewire::test(StockList::class)
            ->call('openImport')
            ->set('importFile', $this->xlsxUpload())
            ->call('import')
            ->assertHasErrors(['importFile'])
            ->assertSet('showImport', true);

        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_close_import_resets_state(): void
    {
        Livewire::test(StockList::class)
            ->call('openImport')
            ->set('importFile', $this->xlsxUpload())
            ->call('closeImport')
            ->assertSet('showImport', false)
            ->assertSet('importFile', null);
    }

    // ------------------------------------------------------------------ AI

    /**
     * @param  array<int, GeneratedStockItem>  $items
     */
    private function stubAi(array $items): void
    {
        $ai = $this->createStub(AiClientInterface::class);
        $ai->method('generate')->willReturn($items);
        $this->app->instance(AiClientInterface::class, $ai);
    }

    public function test_ai_generate_preview_select_and_save(): void
    {
        $this->stubAi([
            new GeneratedStockItem('AI Beras', 'AI-1', 'Deskripsi', 10, 'kg', 12000.0, 3),
            new GeneratedStockItem('AI Gula', null, null, 2, 'kg', null, 5),
            new GeneratedStockItem('AI Kopi'),
        ]);

        $component = Livewire::test(StockList::class)
            ->call('openAi')
            ->assertSet('showAi', true)
            ->set('aiPrompt', 'stok warung kelontong')
            ->call('generateAi')
            ->assertHasNoErrors()
            ->assertSet('aiError', null)
            ->assertCount('aiItems', 3)
            ->assertCount('aiSelected', 3);

        $component->set('aiSelected', [0, 1])
            ->call('saveAi')
            ->assertSet('showAi', false);

        $this->assertDatabaseHas('stocks', ['user_id' => $this->member->id, 'name' => 'AI Beras', 'status' => 'available']);
        $this->assertDatabaseHas('stocks', ['user_id' => $this->member->id, 'name' => 'AI Gula', 'status' => 'low_stock']);
        $this->assertDatabaseMissing('stocks', ['name' => 'AI Kopi']);
    }

    public function test_ai_prompt_is_required(): void
    {
        $this->stubAi([]);

        Livewire::test(StockList::class)
            ->call('openAi')
            ->set('aiPrompt', '')
            ->call('generateAi')
            ->assertHasErrors(['aiPrompt' => 'required']);
    }

    public function test_ai_failure_shows_error_message(): void
    {
        $ai = $this->createStub(AiClientInterface::class);
        $ai->method('generate')->willThrowException(new \RuntimeException('quota'));
        $this->app->instance(AiClientInterface::class, $ai);

        Livewire::test(StockList::class)
            ->call('openAi')
            ->set('aiPrompt', 'apa saja')
            ->call('generateAi')
            ->assertSet('aiError', 'Gagal menghasilkan item. Periksa konfigurasi API key dan koneksi.')
            ->assertSet('aiItems', []);
    }

    public function test_ai_save_revalidates_tampered_client_state(): void
    {
        $component = Livewire::test(StockList::class)
            ->call('openAi')
            ->set('aiItems', [
                ['name' => '', 'sku' => null, 'description' => null, 'quantity' => 1, 'unit' => 'pcs', 'price' => null, 'min_stock' => 5],
                ['name' => 'Valid', 'sku' => null, 'description' => null, 'quantity' => 1, 'unit' => 'pcs', 'price' => null, 'min_stock' => 5],
                ['name' => 'Negatif', 'sku' => null, 'description' => null, 'quantity' => -50, 'unit' => 'pcs', 'price' => null, 'min_stock' => 5],
            ])
            ->set('aiSelected', [0, 1, 2, 99]);

        $component->call('saveAi');

        $this->assertDatabaseCount('stocks', 1);
        $this->assertDatabaseHas('stocks', ['name' => 'Valid', 'user_id' => $this->member->id]);
    }

    public function test_close_ai_resets_state(): void
    {
        Livewire::test(StockList::class)
            ->call('openAi')
            ->set('aiPrompt', 'x')
            ->call('closeAi')
            ->assertSet('showAi', false)
            ->assertSet('aiPrompt', '');
    }
}
