<?php

namespace App\Features\Stock\Livewire;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Features\Stock\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Stok Barang')]
class StockList extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public int|string $quantity = 0;

    public string $unit = 'pcs';

    public string $price = '';

    public int|string $min_stock = 5;

    public bool $showImport = false;

    public $importFile = null;

    public bool $showAi = false;

    public string $aiPrompt = '';

    public ?string $aiError = null;

    /** @var array<int, array<string, mixed>> */
    public array $aiItems = [];

    /** @var array<int, int|string> */
    public array $aiSelected = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        Gate::authorize('create', Stock::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id, StockService $service): void
    {
        $stock = $service->find($id);
        Gate::authorize('update', $stock);

        $this->resetForm();
        $this->editingId = $stock->id;
        $this->name = $stock->name;
        $this->sku = (string) $stock->sku;
        $this->description = (string) $stock->description;
        $this->quantity = $stock->quantity;
        $this->unit = $stock->unit;
        $this->price = $stock->price === null ? '' : (string) $stock->price;
        $this->min_stock = $stock->min_stock;
        $this->showForm = true;
    }

    public function save(StockService $service): void
    {
        $data = $this->validate(StockService::rules());

        if ($this->editingId === null) {
            Gate::authorize('create', Stock::class);
            $service->create(Auth::user(), $data);
            session()->flash('status', 'Stok berhasil ditambahkan.');
        } else {
            $stock = $service->find($this->editingId);
            Gate::authorize('update', $stock);
            $service->update($stock, $data);
            session()->flash('status', 'Stok berhasil diperbarui.');
        }

        $this->closeForm();
    }

    public function closeForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $id, StockService $service): void
    {
        $stock = $service->find($id);
        Gate::authorize('delete', $stock);

        $service->delete($stock);
        session()->flash('status', 'Stok berhasil dihapus.');
    }

    public function adjust(int $id, int $delta, StockService $service): void
    {
        $stock = $service->find($id);
        Gate::authorize('adjustQuantity', $stock);

        $service->adjustQuantity($stock, $delta);
    }

    public function openImport(): void
    {
        Gate::authorize('create', Stock::class);

        $this->reset('importFile');
        $this->resetValidation();
        $this->showImport = true;
    }

    public function closeImport(): void
    {
        $this->reset('importFile');
        $this->resetValidation();
        $this->showImport = false;
    }

    public function import(StockExcelService $excel): void
    {
        Gate::authorize('create', Stock::class);
        $this->validate(['importFile' => 'required|file|mimes:xlsx,xls,csv|max:2048']);

        try {
            $count = $excel->import(Auth::user(), $this->importFile->getRealPath());
        } catch (SpreadsheetException) {
            $this->addError('importFile', 'File tidak dapat dibaca. Gunakan format xlsx/xls/csv yang valid.');

            return;
        }

        session()->flash('status', "{$count} stok berhasil diimpor.");
        $this->closeImport();
    }

    public function export(StockExcelService $excel, StockService $service)
    {
        Gate::authorize('viewAny', Stock::class);

        $content = $excel->export($service->all(Auth::user()));

        return response()->streamDownload(
            function () use ($content) {
                echo $content;
            },
            'stok-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function openAi(): void
    {
        Gate::authorize('create', Stock::class);

        $this->resetAi();
        $this->showAi = true;
    }

    public function closeAi(): void
    {
        $this->resetAi();
        $this->showAi = false;
    }

    public function generateAi(AiClientInterface $ai): void
    {
        Gate::authorize('create', Stock::class);
        $this->validate(['aiPrompt' => 'required|string|max:500']);

        $this->aiError = null;
        $this->aiItems = [];
        $this->aiSelected = [];

        try {
            $items = $ai->generate($this->aiPrompt, 5);
        } catch (Throwable $e) {
            report($e);
            $this->aiError = 'Gagal menghasilkan item. Periksa konfigurasi API key dan koneksi.';

            return;
        }

        $this->aiItems = array_map(fn ($item) => $item->toArray(), $items);
        $this->aiSelected = array_keys($this->aiItems);
    }

    public function saveAi(StockService $service): void
    {
        Gate::authorize('create', Stock::class);

        $saved = 0;

        foreach ($this->aiSelected as $index) {
            $item = $this->aiItems[(int) $index] ?? null;

            // aiItems adalah state publik Livewire (bisa dimanipulasi klien), jadi divalidasi ulang.
            if ($item === null || Validator::make($item, StockService::rules())->fails()) {
                continue;
            }

            $service->create(Auth::user(), $item);
            $saved++;
        }

        session()->flash('status', "{$saved} stok dari AI berhasil disimpan.");
        $this->closeAi();
    }

    public function render(StockService $service)
    {
        return view('livewire.stock.stock-list', [
            'stocks' => $service->list(Auth::user(), $this->search ?: null, $this->statusFilter ?: null),
            'statuses' => StockStatus::cases(),
        ]);
    }

    private function resetAi(): void
    {
        $this->reset(['aiPrompt', 'aiError', 'aiItems', 'aiSelected']);
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'sku', 'description', 'quantity', 'unit', 'price', 'min_stock']);
        $this->resetValidation();
    }
}
