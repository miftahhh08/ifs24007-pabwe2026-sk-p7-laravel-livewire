<?php

namespace App\Features\Stock\Services;

use App\Models\User;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class StockExcelService
{
    /** @var array<int, string> */
    public const HEADERS = ['name', 'sku', 'description', 'qty', 'unit', 'price', 'min_stock', 'status'];

    public function __construct(private readonly StockService $stocks) {}

    /**
     * @param  iterable<int, \App\Features\Stock\Models\Stock>  $stocks
     * @return string isi file .xlsx (binary)
     */
    public function export(iterable $stocks): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stok');

        foreach (self::HEADERS as $index => $header) {
            $sheet->setCellValueExplicit([$index + 1, 1], $header, DataType::TYPE_STRING);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $row = 2;
        foreach ($stocks as $stock) {
            // Teks ditulis eksplisit sebagai string agar tidak dieksekusi sebagai rumus Excel.
            $sheet->setCellValueExplicit([1, $row], (string) $stock->name, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit([2, $row], (string) $stock->sku, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit([3, $row], (string) $stock->description, DataType::TYPE_STRING);
            $sheet->setCellValue([4, $row], $stock->quantity);
            $sheet->setCellValueExplicit([5, $row], (string) $stock->unit, DataType::TYPE_STRING);
            $sheet->setCellValue([6, $row], $stock->price === null ? null : (float) $stock->price);
            $sheet->setCellValue([7, $row], $stock->min_stock);
            $sheet->setCellValueExplicit([8, $row], $stock->status->value, DataType::TYPE_STRING);
            $row++;
        }

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'stock-export-');
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
        $content = (string) file_get_contents($path);
        unlink($path);

        return $content;
    }

    /**
     * Impor stok milik $user dari file xlsx/xls/csv. Baris tanpa nama dilewati.
     * Status selalu dihitung ulang dari qty dan min_stock.
     *
     * @return int jumlah baris yang berhasil diimpor
     */
    public function import(User $user, string $path): int
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $rows = $reader->load($path)->getActiveSheet()->toArray(null, false, true, false);

        $header = array_map(
            fn ($cell) => strtolower(trim((string) $cell)),
            array_shift($rows) ?? []
        );

        $count = 0;

        foreach ($rows as $row) {
            $row = array_slice(array_pad($row, count($header), null), 0, count($header));
            $record = array_combine($header, $row);

            $name = trim((string) ($record['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $quantity = $record['qty'] ?? $record['quantity'] ?? 0;
            $minStock = $record['min_stock'] ?? null;
            $price = $record['price'] ?? null;

            $this->stocks->create($user, [
                'name' => mb_substr($name, 0, 255),
                'sku' => $this->text($record['sku'] ?? null, 100),
                'description' => $this->text($record['description'] ?? null, 5000),
                'quantity' => is_numeric($quantity) ? max(0, (int) $quantity) : 0,
                'unit' => $this->text($record['unit'] ?? null, 20) ?? 'pcs',
                'price' => is_numeric($price) ? max(0, (float) $price) : null,
                'min_stock' => is_numeric($minStock) ? max(0, (int) $minStock) : 5,
            ]);

            $count++;
        }

        return $count;
    }

    private function text(mixed $value, int $max): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
