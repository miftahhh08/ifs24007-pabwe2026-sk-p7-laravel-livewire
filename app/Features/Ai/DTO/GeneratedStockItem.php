<?php

namespace App\Features\Ai\DTO;

final class GeneratedStockItem
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $sku = null,
        public readonly ?string $description = null,
        public readonly int $quantity = 0,
        public readonly string $unit = 'pcs',
        public readonly ?float $price = null,
        public readonly int $minStock = 5,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $text = static function (mixed $value, int $max): ?string {
            if (! is_scalar($value)) {
                return null;
            }

            $value = trim((string) $value);

            return $value === '' ? null : mb_substr($value, 0, $max);
        };

        $price = $data['price'] ?? null;
        $minStock = $data['min_stock'] ?? null;

        return new self(
            name: $text($data['name'] ?? null, 255) ?? '',
            sku: $text($data['sku'] ?? null, 100),
            description: $text($data['description'] ?? null, 5000),
            quantity: is_numeric($data['quantity'] ?? null) ? max(0, (int) $data['quantity']) : 0,
            unit: $text($data['unit'] ?? null, 20) ?? 'pcs',
            price: is_numeric($price) ? max(0.0, (float) $price) : null,
            minStock: is_numeric($minStock) ? max(0, (int) $minStock) : 5,
        );
    }

    /**
     * Bentuk array sesuai kolom form stok (StockService::rules()).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'price' => $this->price,
            'min_stock' => $this->minStock,
        ];
    }
}
