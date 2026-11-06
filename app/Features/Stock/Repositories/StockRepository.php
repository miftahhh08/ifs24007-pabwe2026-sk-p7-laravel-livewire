<?php

namespace App\Features\Stock\Repositories;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StockRepository implements StockRepositoryInterface
{
    public function paginateForUser(int $userId, ?string $search, ?string $status, int $perPage = 10): LengthAwarePaginator
    {
        return Stock::query()
            ->where('user_id', $userId)
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function allForUser(int $userId): Collection
    {
        return Stock::query()->where('user_id', $userId)->orderBy('id')->get();
    }

    public function findOrFail(int $id): Stock
    {
        return Stock::query()->findOrFail($id);
    }

    public function create(array $data): Stock
    {
        return Stock::query()->create($data);
    }

    public function update(Stock $stock, array $data): Stock
    {
        $stock->update($data);

        return $stock->refresh();
    }

    public function delete(Stock $stock): void
    {
        $stock->delete();
    }
}
