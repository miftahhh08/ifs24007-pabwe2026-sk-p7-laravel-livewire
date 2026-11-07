<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedStockItem;
use PHPUnit\Framework\TestCase;

class GeneratedStockItemTest extends TestCase
{
    public function test_from_array_maps_all_fields(): void
    {
        $item = GeneratedStockItem::fromArray([
            'name' => '  Beras 5kg ',
            'sku' => 'BRS-01',
            'description' => 'Beras **premium**',
            'quantity' => '12',
            'unit' => 'karung',
            'price' => '65000.5',
            'min_stock' => 3,
        ]);

        $this->assertSame('Beras 5kg', $item->name);
        $this->assertSame('BRS-01', $item->sku);
        $this->assertSame('Beras **premium**', $item->description);
        $this->assertSame(12, $item->quantity);
        $this->assertSame('karung', $item->unit);
        $this->assertSame(65000.5, $item->price);
        $this->assertSame(3, $item->minStock);
    }

    public function test_from_array_uses_defaults_for_missing_or_invalid_values(): void
    {
        $item = GeneratedStockItem::fromArray([
            'name' => ['bukan', 'string'],
            'sku' => '   ',
            'quantity' => 'banyak',
            'price' => 'gratis',
            'min_stock' => 'x',
        ]);

        $this->assertSame('', $item->name);
        $this->assertNull($item->sku);
        $this->assertNull($item->description);
        $this->assertSame(0, $item->quantity);
        $this->assertSame('pcs', $item->unit);
        $this->assertNull($item->price);
        $this->assertSame(5, $item->minStock);
    }

    public function test_from_array_clamps_negative_numbers_and_truncates_text(): void
    {
        $item = GeneratedStockItem::fromArray([
            'name' => str_repeat('a', 300),
            'sku' => str_repeat('b', 150),
            'description' => str_repeat('c', 6000),
            'unit' => str_repeat('d', 50),
            'quantity' => -4,
            'price' => -100,
            'min_stock' => -1,
        ]);

        $this->assertSame(255, mb_strlen($item->name));
        $this->assertSame(100, mb_strlen((string) $item->sku));
        $this->assertSame(5000, mb_strlen((string) $item->description));
        $this->assertSame(20, mb_strlen($item->unit));
        $this->assertSame(0, $item->quantity);
        $this->assertSame(0.0, $item->price);
        $this->assertSame(0, $item->minStock);
    }

    public function test_constructor_defaults_and_to_array(): void
    {
        $item = new GeneratedStockItem('Pulpen');

        $this->assertSame([
            'name' => 'Pulpen',
            'sku' => null,
            'description' => null,
            'quantity' => 0,
            'unit' => 'pcs',
            'price' => null,
            'min_stock' => 5,
        ], $item->toArray());
    }
}
