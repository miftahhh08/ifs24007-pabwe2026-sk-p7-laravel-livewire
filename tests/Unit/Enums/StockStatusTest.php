<?php

namespace Tests\Unit\Enums;

use App\Features\Stock\Enums\StockStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StockStatusTest extends TestCase
{
    public function test_values(): void
    {
        $this->assertSame('available', StockStatus::Available->value);
        $this->assertSame('low_stock', StockStatus::LowStock->value);
        $this->assertSame('out_of_stock', StockStatus::OutOfStock->value);
    }

    public function test_labels(): void
    {
        $this->assertSame('Tersedia', StockStatus::Available->label());
        $this->assertSame('Stok Menipis', StockStatus::LowStock->label());
        $this->assertSame('Stok Habis', StockStatus::OutOfStock->label());
    }

    public function test_badge_classes_are_green_yellow_red(): void
    {
        $this->assertStringContainsString('green', StockStatus::Available->badgeClass());
        $this->assertStringContainsString('yellow', StockStatus::LowStock->badgeClass());
        $this->assertStringContainsString('red', StockStatus::OutOfStock->badgeClass());
    }

    #[DataProvider('quantityProvider')]
    public function test_from_quantity(int $quantity, int $minStock, StockStatus $expected): void
    {
        $this->assertSame($expected, StockStatus::fromQuantity($quantity, $minStock));
    }

    /**
     * @return array<string, array{int, int, StockStatus}>
     */
    public static function quantityProvider(): array
    {
        return [
            'nol = habis' => [0, 5, StockStatus::OutOfStock],
            'negatif = habis' => [-3, 5, StockStatus::OutOfStock],
            'di bawah ambang = menipis' => [3, 5, StockStatus::LowStock],
            'tepat di ambang = menipis' => [5, 5, StockStatus::LowStock],
            'di atas ambang = tersedia' => [6, 5, StockStatus::Available],
            'ambang nol, qty 1 = tersedia' => [1, 0, StockStatus::Available],
        ];
    }
}
