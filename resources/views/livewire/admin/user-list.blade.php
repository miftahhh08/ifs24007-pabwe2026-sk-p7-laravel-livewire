<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Daftar Pengguna</h1>
        <input type="search" id="user-search" wire:model.live.debounce.300ms="search" placeholder="Cari nama, username, email..."
               class="w-72 rounded border border-gray-300 px-3 py-2 text-sm">
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100 text-left text-xs uppercase text-gray-600">
            <tr>
                <th class="px-4 py-3">Nama</th>
                <th class="px-4 py-3">Username</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Role</th>
                <th class="px-4 py-3">Jumlah Stok</th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($users as $user)
                <tr wire:key="user-{{ $user->id }}">
                    <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                    <td class="px-4 py-3">{{ $user->username }}</td>
                    <td class="px-4 py-3">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2 py-1 text-xs font-medium {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">{{ $user->role }}</span>
                    </td>
                    <td class="px-4 py-3">{{ $user->stocks_count }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ url('/admin/users/'.$user->id.'/stocks') }}" class="text-blue-600 hover:underline">Lihat stok</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada pengguna.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
