<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StockService
{
    public function __construct(private readonly StockRepositoryInterface $repository) {}

    /**
     * Aturan validasi bersama untuk form stok (nama properti = nama kolom).
     *
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'quantity' => 'required|integer|min:0',
            'unit' => 'required|string|max:20',
            'price' => 'nullable|numeric|min:0',
            'min_stock' => 'required|integer|min:0',
        ];
    }

    public function list(User $user, ?string $search, ?string $status, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForUser($user->id, $search, $status, $perPage);
    }

    /**
     * @return Collection<int, Stock>
     */
    public function all(User $user): Collection
    {
        return $this->repository->allForUser($user->id);
    }

    public function find(int $id): Stock
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Stock
    {
        $data = $this->normalize($data);
        $data['user_id'] = $user->id;

        return $this->repository->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Stock $stock, array $data): Stock
    {
        return $this->repository->update($stock, $this->normalize($data));
    }

    public function delete(Stock $stock): void
    {
        $this->deleteCoverFile($stock);
        $this->repository->delete($stock);
    }

    public function adjustQuantity(Stock $stock, int $delta): Stock
    {
        $quantity = max(0, $stock->quantity + $delta);

        return $this->repository->update($stock, [
            'quantity' => $quantity,
            'status' => StockStatus::fromQuantity($quantity, $stock->min_stock),
        ]);
    }

    public function uploadCover(Stock $stock, UploadedFile $file): Stock
    {
        $this->deleteCoverFile($stock);
        $path = $file->store('stock-covers', 'public');

        return $this->repository->update($stock, ['cover_image_path' => $path]);
    }

    public function removeCover(Stock $stock): Stock
    {
        $this->deleteCoverFile($stock);

        return $this->repository->update($stock, ['cover_image_path' => null]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        foreach (['sku', 'description', 'price'] as $field) {
            if (array_key_exists($field, $data) && ($data[$field] === '' || $data[$field] === null)) {
                $data[$field] = null;
            }
        }

        $data['quantity'] = (int) $data['quantity'];
        $data['min_stock'] = (int) $data['min_stock'];
        $data['status'] = StockStatus::fromQuantity($data['quantity'], $data['min_stock']);

        return $data;
    }

    private function deleteCoverFile(Stock $stock): void
    {
        if ($stock->cover_image_path) {
            Storage::disk('public')->delete($stock->cover_image_path);
        }
    }
}
