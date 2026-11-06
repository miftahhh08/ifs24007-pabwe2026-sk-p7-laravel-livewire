<?php

namespace Database\Seeders;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'admin',
            'role' => 'admin',
        ]);

        $member = User::create([
            'name' => 'Member',
            'username' => 'member',
            'email' => 'member@example.com',
            'password' => 'member',
            'role' => 'member',
        ]);

        $items = [
            ['Beras 5kg', 'BRS-001', 40, 'karung', 65000],
            ['Minyak Goreng 1L', 'MYK-002', 4, 'botol', 18000],
            ['Gula Pasir 1kg', 'GLA-003', 0, 'kg', 16000],
            ['Kopi Bubuk 200g', 'KPI-004', 25, 'pack', 22000],
        ];

        foreach ($items as [$name, $sku, $qty, $unit, $price]) {
            Stock::create([
                'user_id' => $member->id,
                'name' => $name,
                'sku' => $sku,
                'description' => "Stok **{$name}**",
                'quantity' => $qty,
                'unit' => $unit,
                'price' => $price,
                'min_stock' => 5,
                'status' => StockStatus::fromQuantity($qty, 5),
            ]);
        }
    }
}
