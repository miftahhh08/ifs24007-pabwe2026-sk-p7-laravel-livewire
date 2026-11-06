<?php

namespace App\Features\Stock\Livewire;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Detail Stok')]
class StockDetail extends Component
{
    use WithFileUploads;

    public Stock $stock;

    public bool $editing = false;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public int|string $quantity = 0;

    public string $unit = 'pcs';

    public string $price = '';

    public int|string $min_stock = 5;

    public $cover = null;

    public function mount(Stock $stock): void
    {
        Gate::authorize('view', $stock);

        $this->stock = $stock;
    }

    public function edit(): void
    {
        Gate::authorize('update', $this->stock);

        $this->name = $this->stock->name;
        $this->sku = (string) $this->stock->sku;
        $this->description = (string) $this->stock->description;
        $this->quantity = $this->stock->quantity;
        $this->unit = $this->stock->unit;
        $this->price = $this->stock->price === null ? '' : (string) $this->stock->price;
        $this->min_stock = $this->stock->min_stock;
        $this->resetValidation();
        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->resetValidation();
        $this->editing = false;
    }

    public function save(StockService $service): void
    {
        Gate::authorize('update', $this->stock);
        $data = $this->validate(StockService::rules());

        $this->stock = $service->update($this->stock, $data);
        $this->editing = false;
        session()->flash('status', 'Stok berhasil diperbarui.');
    }

    public function adjust(int $delta, StockService $service): void
    {
        Gate::authorize('adjustQuantity', $this->stock);

        $this->stock = $service->adjustQuantity($this->stock, $delta);
    }

    public function saveCover(StockService $service): void
    {
        Gate::authorize('update', $this->stock);
        $this->validate(['cover' => 'required|image|max:2048']);

        $this->stock = $service->uploadCover($this->stock, $this->cover);
        $this->cover = null;
        session()->flash('status', 'Cover berhasil diunggah.');
    }

    public function removeCover(StockService $service): void
    {
        Gate::authorize('update', $this->stock);

        $this->stock = $service->removeCover($this->stock);
        session()->flash('status', 'Cover dihapus.');
    }

    public function delete(StockService $service)
    {
        Gate::authorize('delete', $this->stock);

        $service->delete($this->stock);
        session()->flash('status', 'Stok berhasil dihapus.');

        return $this->redirect('/stocks');
    }

    public function render()
    {
        return view('livewire.stock.stock-detail');
    }
}
