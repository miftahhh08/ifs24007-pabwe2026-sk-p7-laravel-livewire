<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class StockExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockExcelService $service;

    /** @var array<int, string> */
    private array $tmp = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StockExcelService::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->tmp as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function xlsx(array $rows, string $format = 'Xlsx', string $ext = 'xlsx'): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1', true);

        $path = tempnam(sys_get_temp_dir(), 'stk-test-').'.'.$ext;
        IOFactory::createWriter($spreadsheet, $format)->save($path);

        return $this->tmp[] = $path;
    }

    public function test_export_writes_headers_and_rows(): void
    {
        $user = User::factory()->create();
        Stock::factory()->create([
            'user_id' => $user->id, 'name' => 'Beras', 'sku' => 'BRS-1', 'description' => 'Putih',
            'quantity' => 12, 'unit' => 'kg', 'price' => 13500.5, 'min_stock' => 4, 'status' => 'available',
        ]);
        Stock::factory()->create([
            'user_id' => $user->id, 'name' => 'Tanpa Harga', 'sku' => null, 'description' => null,
            'quantity' => 0, 'price' => null, 'status' => 'out_of_stock',
        ]);

        $binary = $this->service->export(Stock::query()->orderBy('id')->get());

        $this->assertStringStartsWith('PK', $binary);

        $path = $this->tmp[] = tempnam(sys_get_temp_dir(), 'stk-read-');
        file_put_contents($path, $binary);
        $sheet = IOFactory::createReaderForFile($path)->load($path)->getActiveSheet();
        $rows = $sheet->toArray(null, false, true, false);

        $this->assertSame(StockExcelService::HEADERS, $rows[0]);
        $this->assertSame('Beras', $rows[1][0]);
        $this->assertSame('BRS-1', $rows[1][1]);
        $this->assertEquals(12, $rows[1][3]);
        $this->assertEquals(13500.5, $rows[1][5]);
        $this->assertSame('available', $rows[1][7]);
        $this->assertSame('Tanpa Harga', $rows[2][0]);
        $this->assertNull($rows[2][5]);
        $this->assertSame('out_of_stock', $rows[2][7]);
        $this->assertSame('Stok', $sheet->getTitle());
    }

    public function test_export_does_not_execute_formulas(): void
    {
        Stock::factory()->create(['name' => '=1+1', 'description' => '=HYPERLINK("http://x")']);

        $binary = $this->service->export(Stock::query()->get());

        $path = $this->tmp[] = tempnam(sys_get_temp_dir(), 'stk-read-');
        file_put_contents($path, $binary);
        $cell = IOFactory::createReaderForFile($path)->load($path)->getActiveSheet()->getCell('A2');

        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertSame('=1+1', $cell->getValue());
    }

    public function test_export_empty_collection_has_only_header(): void
    {
        $binary = $this->service->export([]);

        $path = $this->tmp[] = tempnam(sys_get_temp_dir(), 'stk-read-');
        file_put_contents($path, $binary);
        $rows = IOFactory::createReaderForFile($path)->load($path)->getActiveSheet()->toArray(null, false, true, false);

        $this->assertCount(1, $rows);
    }

    public function test_import_reads_rows_and_recomputes_status(): void
    {
        $user = User::factory()->create();
        $path = $this->xlsx([
            ['name', 'sku', 'description', 'qty', 'unit', 'price', 'min_stock', 'status'],
            ['Beras', 'BRS-1', 'Putih', 40, 'kg', 13500, 5, 'out_of_stock'],
            ['Gula', 'GLA-1', null, 3, 'kg', null, 5, 'available'],
            ['Kopi', null, null, 0, null, 'abc', null, null],
        ]);

        $count = $this->service->import($user, $path);

        $this->assertSame(3, $count);
        $beras = Stock::query()->where('name', 'Beras')->first();
        $this->assertSame($user->id, $beras->user_id);
        $this->assertSame(StockStatus::Available, $beras->status, 'status dihitung ulang, bukan dari kolom file');
        $this->assertSame(StockStatus::LowStock, Stock::query()->where('name', 'Gula')->first()->status);

        $kopi = Stock::query()->where('name', 'Kopi')->first();
        $this->assertSame('pcs', $kopi->unit);
        $this->assertNull($kopi->price);
        $this->assertSame(5, $kopi->min_stock);
        $this->assertSame(StockStatus::OutOfStock, $kopi->status);
    }

    public function test_import_skips_rows_without_name_and_supports_quantity_alias_and_short_rows(): void
    {
        $user = User::factory()->create();
        $path = $this->xlsx([
            ['Name', 'Quantity', 'Unit'],
            ['Pulpen', 20, 'box'],
            [null, 5, 'pcs'],
            ['Buku'],
        ]);

        $this->assertSame(2, $this->service->import($user, $path));
        $this->assertSame(20, Stock::query()->where('name', 'Pulpen')->first()->quantity);
        $this->assertSame(0, Stock::query()->where('name', 'Buku')->first()->quantity);
    }

    public function test_import_clamps_negative_numbers_and_truncates_long_text(): void
    {
        $user = User::factory()->create();
        $path = $this->xlsx([
            ['name', 'qty', 'price', 'min_stock', 'unit'],
            [str_repeat('n', 300), -9, -50, -2, str_repeat('u', 40)],
        ]);

        $this->service->import($user, $path);

        $stock = Stock::query()->first();
        $this->assertSame(255, mb_strlen($stock->name));
        $this->assertSame(0, $stock->quantity);
        $this->assertSame('0.00', $stock->price);
        $this->assertSame(0, $stock->min_stock);
        $this->assertSame(20, mb_strlen($stock->unit));
    }

    public function test_import_csv(): void
    {
        $user = User::factory()->create();
        $path = tempnam(sys_get_temp_dir(), 'stk-csv-').'.csv';
        $this->tmp[] = $path;
        file_put_contents($path, "name,qty,unit\nTeh,9,pack\n");

        $this->assertSame(1, $this->service->import($user, $path));
        $this->assertDatabaseHas('stocks', ['user_id' => $user->id, 'name' => 'Teh', 'quantity' => 9, 'unit' => 'pack']);
    }

    public function test_import_empty_sheet_imports_nothing(): void
    {
        $user = User::factory()->create();

        $this->assertSame(0, $this->service->import($user, $this->xlsx([[]])));
        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_import_unreadable_file_throws_spreadsheet_exception(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'stk-bad-').'.xlsx';
        $this->tmp[] = $path;
        file_put_contents($path, "\x00\x01\x02 bukan spreadsheet");

        $this->expectException(ReaderException::class);
        $this->service->import(User::factory()->create(), $path);
    }
}
