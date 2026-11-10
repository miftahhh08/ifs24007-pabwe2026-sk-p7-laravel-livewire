<div>
    @push('styles')
        <link rel="stylesheet" href="https://uicdn.toast.com/editor/3.2.2/toastui-editor.min.css">
    @endpush
    @push('scripts')
        <script src="https://uicdn.toast.com/editor/3.2.2/toastui-editor-all.min.js"></script>
    @endpush

    <div class="mb-4">
        <a href="{{ url('/stocks') }}" class="text-sm text-blue-600 hover:underline">&larr; Kembali ke daftar</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-lg bg-white p-6 shadow">
                @if (! $editing)
                    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h1 class="text-2xl font-semibold">{{ $stock->name }}</h1>
                            <p class="text-sm text-gray-500">SKU: {{ $stock->sku ?? '-' }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-sm font-medium {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
                    </div>

                    <dl class="mb-4 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Harga</dt>
                            <dd class="font-medium">{{ $stock->price === null ? '-' : 'Rp '.number_format((float) $stock->price, 0, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Stok minimum</dt>
                            <dd class="font-medium">{{ $stock->min_stock }} {{ $stock->unit }}</dd>
                        </div>
                    </dl>

                    <div class="mb-4 flex items-center gap-3">
                        <span class="text-sm text-gray-500">Jumlah</span>
                        <button type="button" wire:click="adjust(-1)" aria-label="Kurangi jumlah" class="h-8 w-8 rounded border border-gray-300 hover:bg-gray-100">−</button>
                        <span id="stock-quantity-value" class="min-w-[4rem] text-center text-lg font-semibold">{{ $stock->quantity }} {{ $stock->unit }}</span>
                        <button type="button" wire:click="adjust(1)" aria-label="Tambah jumlah" class="h-8 w-8 rounded border border-gray-300 hover:bg-gray-100">+</button>
                    </div>

                    <div class="mb-2 text-sm font-medium text-gray-500">Deskripsi</div>
                    @if ($stock->description)
                        <div class="space-y-2 text-sm [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_h1]:text-xl [&_h1]:font-semibold [&_h2]:text-lg [&_h2]:font-semibold [&_a]:text-blue-700 [&_a]:underline [&_code]:rounded [&_code]:bg-gray-100 [&_code]:px-1">
                            {!! \Illuminate\Support\Str::markdown($stock->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                        </div>
                    @else
                        <p class="text-sm text-gray-400">Belum ada deskripsi.</p>
                    @endif

                    <div class="mt-6 flex gap-2">
                        <button type="button" id="stock-edit-button" wire:click="edit" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Edit</button>
                        <button type="button" wire:click="delete" wire:confirm="Hapus stok ini?" class="rounded border border-red-300 px-4 py-2 text-sm text-red-600 hover:bg-red-50">Hapus</button>
                    </div>
                @else
                    <h2 class="mb-4 text-lg font-semibold">Edit Stok</h2>
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
                            <span class="mb-1 block text-sm font-medium">Deskripsi (Markdown)</span>
                            <div wire:ignore
                                 x-data="{ editor: null }"
                                 x-init="
                                    if (window.toastui) {
                                        editor = new toastui.Editor({
                                            el: $refs.editor,
                                            height: '280px',
                                            initialEditType: 'markdown',
                                            previewStyle: 'vertical',
                                            initialValue: $wire.description ?? '',
                                        });
                                        editor.on('change', () => $wire.$set('description', editor.getMarkdown(), false));
                                    }
                                 ">
                                <div x-ref="editor"></div>
                            </div>
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
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="cancel" class="rounded border border-gray-300 px-4 py-2 text-sm hover:bg-gray-100">Batal</button>
                            <button type="submit" id="stock-save" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow h-fit">
            <h2 class="mb-3 text-sm font-semibold text-gray-600">Cover</h2>
            @if ($stock->coverUrl())
                <img src="{{ $stock->coverUrl() }}" alt="Cover {{ $stock->name }}" class="mb-3 w-full rounded object-cover">
                <button type="button" wire:click="removeCover" class="mb-4 text-sm text-red-600 hover:underline">Hapus cover</button>
            @else
                <div class="mb-4 flex h-40 items-center justify-center rounded bg-gray-100 text-sm text-gray-400">Belum ada cover</div>
            @endif

            <input type="file" id="stock-cover" wire:model="cover" accept="image/*" class="mb-2 block w-full text-sm">
            @error('cover') <p class="mb-2 text-sm text-red-600">{{ $message }}</p> @enderror
            <div wire:loading wire:target="cover" class="mb-2 text-xs text-gray-500">Mengunggah...</div>
            <button type="button" id="stock-cover-save" wire:click="saveCover"
                    class="rounded bg-gray-800 px-3 py-2 text-sm text-white hover:bg-gray-700">Simpan cover</button>
        </div>
    </div>
</div>
