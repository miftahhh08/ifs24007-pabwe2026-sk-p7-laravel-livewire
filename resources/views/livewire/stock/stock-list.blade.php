<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Stok Barang</h1>
        <div class="flex flex-wrap gap-2">
            <button type="button" id="stock-ai-button" wire:click="openAi"
                    class="rounded bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700">Generate AI</button>
            <button type="button" id="stock-import-button" wire:click="openImport"
                    class="rounded border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-100">Impor Excel</button>
            <button type="button" id="stock-export-button" wire:click="export"
                    class="rounded border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-100">Ekspor Excel</button>
            <button type="button" id="stock-create-button" wire:click="openCreate"
                    class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">+ Tambah Stok</button>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <div class="mb-4 flex flex-wrap gap-3">
        <input type="search" id="stock-search" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau SKU..."
               class="w-64 rounded border border-gray-300 px-3 py-2 text-sm">
        <select id="stock-status-filter" wire:model.live="statusFilter" class="rounded border border-gray-300 px-3 py-2 text-sm">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100 text-left text-xs uppercase text-gray-600">
            <tr>
                <th class="px-4 py-3">Nama</th>
                <th class="px-4 py-3">SKU</th>
                <th class="px-4 py-3">Jumlah</th>
                <th class="px-4 py-3">Harga</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($stocks as $stock)
                <tr wire:key="stock-{{ $stock->id }}">
                    <td class="px-4 py-3">
                        <a href="{{ url('/stocks/'.$stock->id) }}" class="font-medium text-blue-600 hover:underline">{{ $stock->name }}</a>
                    </td>
                    <td class="px-4 py-3">{{ $stock->sku ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="adjust({{ $stock->id }}, -1)" aria-label="Kurangi jumlah"
                                    class="h-7 w-7 rounded border border-gray-300 hover:bg-gray-100">−</button>
                            <span class="min-w-[3rem] text-center">{{ $stock->quantity }} {{ $stock->unit }}</span>
                            <button type="button" wire:click="adjust({{ $stock->id }}, 1)" aria-label="Tambah jumlah"
                                    class="h-7 w-7 rounded border border-gray-300 hover:bg-gray-100">+</button>
                        </div>
                    </td>
                    <td class="px-4 py-3">{{ $stock->price === null ? '-' : 'Rp '.number_format((float) $stock->price, 0, ',', '.') }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2 py-1 text-xs font-medium {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ url('/stocks/'.$stock->id) }}" class="mr-2 text-gray-600 hover:underline">Detail</a>
                        <button type="button" wire:click="openEdit({{ $stock->id }})" class="mr-2 text-blue-600 hover:underline">Edit</button>
                        <button type="button" wire:click="delete({{ $stock->id }})" wire:confirm="Hapus stok ini?"
                                class="text-red-600 hover:underline">Hapus</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada stok.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $stocks->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-semibold">{{ $editingId ? 'Edit Stok' : 'Tambah Stok' }}</h2>

                <form wire:submit="save" class="space-y-3">
                    <div>
                        <label for="stock-name" class="mb-1 block text-sm font-medium">Nama</label>
                        <input type="text" id="stock-name" wire:model="name" class="w-full rounded border border-gray-300 px-3 py-2">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="stock-sku" class="mb-1 block text-sm font-medium">SKU</label>
                        <input type="text" id="stock-sku" wire:model="sku" class="w-full rounded border border-gray-300 px-3 py-2">
                        @error('sku') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="stock-description" class="mb-1 block text-sm font-medium">Deskripsi (Markdown)</label>
                        <textarea id="stock-description" wire:model="description" rows="3" class="w-full rounded border border-gray-300 px-3 py-2"></textarea>
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="stock-quantity" class="mb-1 block text-sm font-medium">Jumlah</label>
                            <input type="number" min="0" id="stock-quantity" wire:model="quantity" class="w-full rounded border border-gray-300 px-3 py-2">
                            @error('quantity') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="stock-unit" class="mb-1 block text-sm font-medium">Satuan</label>
                            <input type="text" id="stock-unit" wire:model="unit" class="w-full rounded border border-gray-300 px-3 py-2">
                            @error('unit') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="stock-price" class="mb-1 block text-sm font-medium">Harga</label>
                            <input type="number" min="0" step="0.01" id="stock-price" wire:model="price" class="w-full rounded border border-gray-300 px-3 py-2">
                            @error('price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="stock-min-stock" class="mb-1 block text-sm font-medium">Stok Minimum</label>
                            <input type="number" min="0" id="stock-min-stock" wire:model="min_stock" class="w-full rounded border border-gray-300 px-3 py-2">
                            @error('min_stock') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">Status (tersedia / menipis / habis) dihitung otomatis dari jumlah dan stok minimum.</p>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="stock-cancel" wire:click="closeForm" class="rounded border border-gray-300 px-4 py-2 text-sm hover:bg-gray-100">Batal</button>
                        <button type="submit" id="stock-save" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showImport)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                <h2 class="mb-2 text-lg font-semibold">Impor Stok dari Excel</h2>
                <p class="mb-4 text-sm text-gray-500">
                    Format kolom (baris pertama): name, sku, description, qty, unit, price, min_stock, status.
                    Gunakan hasil Ekspor sebagai template. Status dihitung otomatis.
                </p>
                <form wire:submit="import" class="space-y-3">
                    <input type="file" id="stock-import-file" wire:model="importFile" accept=".xlsx,.xls,.csv" class="block w-full text-sm">
                    @error('importFile') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="importFile" class="text-xs text-gray-500">Mengunggah...</div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeImport" class="rounded border border-gray-300 px-4 py-2 text-sm hover:bg-gray-100">Batal</button>
                        <button type="submit" id="stock-import-submit" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Impor</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showAi)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 shadow-xl">
                <h2 class="mb-2 text-lg font-semibold">Generate Item Stok dengan AI</h2>
                <p class="mb-4 text-sm text-gray-500">Contoh: "stok bahan pokok warung kecil" atau "alat tulis kantor".</p>

                <form wire:submit="generateAi" class="mb-4 flex gap-2">
                    <input type="text" id="stock-ai-prompt" wire:model="aiPrompt" placeholder="Deskripsikan kebutuhan stok..."
                           class="flex-1 rounded border border-gray-300 px-3 py-2 text-sm">
                    <button type="submit" id="stock-ai-generate" wire:loading.attr="disabled" wire:target="generateAi"
                            class="rounded bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700">
                        <span wire:loading.remove wire:target="generateAi">Generate</span>
                        <span wire:loading wire:target="generateAi">Memproses...</span>
                    </button>
                </form>
                @error('aiPrompt') <p class="mb-3 text-sm text-red-600">{{ $message }}</p> @enderror
                @if ($aiError) <p class="mb-3 rounded bg-red-50 px-3 py-2 text-sm text-red-700">{{ $aiError }}</p> @endif

                @if (count($aiItems) > 0)
                    <form wire:submit="saveAi">
                        <div class="mb-3 divide-y divide-gray-100 rounded border border-gray-200">
                            @foreach ($aiItems as $index => $item)
                                <label wire:key="ai-{{ $index }}" class="flex cursor-pointer items-start gap-3 p-3 text-sm hover:bg-gray-50">
                                    <input type="checkbox" wire:model="aiSelected" value="{{ $index }}" class="mt-1">
                                    <span class="flex-1">
                                        <span class="block font-medium">{{ $item['name'] }}
                                            <span class="font-normal text-gray-500">({{ $item['sku'] ?? '-' }})</span></span>
                                        <span class="block text-gray-600">{{ $item['quantity'] }} {{ $item['unit'] }}
                                            &middot; min {{ $item['min_stock'] }}
                                            &middot; {{ $item['price'] === null ? '-' : 'Rp '.number_format((float) $item['price'], 0, ',', '.') }}</span>
                                        @if ($item['description'])
                                            <span class="block text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($item['description'], 120) }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="closeAi" class="rounded border border-gray-300 px-4 py-2 text-sm hover:bg-gray-100">Batal</button>
                            <button type="submit" id="stock-ai-save" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan terpilih</button>
                        </div>
                    </form>
                @else
                    <div class="flex justify-end">
                        <button type="button" wire:click="closeAi" class="rounded border border-gray-300 px-4 py-2 text-sm hover:bg-gray-100">Tutup</button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
