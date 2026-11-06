<div>
    <div class="mb-4">
        <a href="{{ url('/admin/users') }}" class="text-sm text-blue-600 hover:underline">&larr; Kembali ke daftar pengguna</a>
    </div>

    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Stok milik {{ $user->name }}</h1>
        <p class="text-sm text-gray-500">&#64;{{ $user->username }} &middot; Tampilan hanya baca (read-only)</p>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
        <input type="search" id="admin-stock-search" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau SKU..."
               class="w-64 rounded border border-gray-300 px-3 py-2 text-sm">
        <select id="admin-stock-status-filter" wire:model.live="statusFilter" class="rounded border border-gray-300 px-3 py-2 text-sm">
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
                <th class="px-4 py-3">Stok Min.</th>
                <th class="px-4 py-3">Harga</th>
                <th class="px-4 py-3">Status</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($stocks as $stock)
                <tr wire:key="admin-stock-{{ $stock->id }}">
                    <td class="px-4 py-3 font-medium">{{ $stock->name }}</td>
                    <td class="px-4 py-3">{{ $stock->sku ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $stock->quantity }} {{ $stock->unit }}</td>
                    <td class="px-4 py-3">{{ $stock->min_stock }}</td>
                    <td class="px-4 py-3">{{ $stock->price === null ? '-' : 'Rp '.number_format((float) $stock->price, 0, ',', '.') }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2 py-1 text-xs font-medium {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">Pengguna ini belum punya stok.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $stocks->links() }}</div>
</div>
