<div class="max-w-3xl space-y-6">
    <h1 class="text-2xl font-semibold">Profil</h1>

    @if (session('status'))
        <div class="rounded bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <section class="rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-semibold">Foto Profil</h2>
        <div class="flex items-center gap-4">
            @if ($user->profilePhotoUrl())
                <img src="{{ $user->profilePhotoUrl() }}" alt="Foto profil" class="h-20 w-20 rounded-full object-cover">
            @else
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-gray-200 text-2xl font-semibold text-gray-600">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            <div class="space-y-2">
                <label for="profile-photo" class="sr-only">Pilih foto profil</label>
                <input type="file" id="profile-photo" wire:model="photo" accept="image/*" class="block text-sm">
                @error('photo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <div wire:loading wire:target="photo" class="text-xs text-gray-500">Mengunggah...</div>
                <div class="flex gap-2">
                    <button type="button" id="profile-photo-save" wire:click="savePhoto"
                            class="rounded bg-gray-800 px-3 py-2 text-sm text-white hover:bg-gray-700">Simpan foto</button>
                    @if ($user->profile_photo_path)
                        <button type="button" wire:click="removePhoto" class="rounded border border-red-300 px-3 py-2 text-sm text-red-600 hover:bg-red-50">Hapus foto</button>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-semibold">Data Akun</h2>
        <form wire:submit="saveProfile" class="space-y-3">
            <div>
                <label for="profile-name" class="mb-1 block text-sm font-medium">Nama</label>
                <input type="text" id="profile-name" wire:model="name" class="w-full rounded border border-gray-300 px-3 py-2">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="profile-username" class="mb-1 block text-sm font-medium">Username</label>
                <input type="text" id="profile-username" wire:model="username" class="w-full rounded border border-gray-300 px-3 py-2">
                @error('username') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="profile-email" class="mb-1 block text-sm font-medium">Email</label>
                <input type="email" id="profile-email" wire:model="email" class="w-full rounded border border-gray-300 px-3 py-2">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" id="profile-save" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan</button>
        </form>
    </section>

    <section class="rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-semibold">Ubah Password</h2>
        <form wire:submit="savePassword" class="space-y-3">
            <div>
                <label for="profile-current-password" class="mb-1 block text-sm font-medium">Password saat ini</label>
                <input type="password" id="profile-current-password" wire:model="current_password" autocomplete="current-password"
                       class="w-full rounded border border-gray-300 px-3 py-2">
                @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="profile-password" class="mb-1 block text-sm font-medium">Password baru</label>
                <input type="password" id="profile-password" wire:model="password" autocomplete="new-password"
                       class="w-full rounded border border-gray-300 px-3 py-2">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="profile-password-confirmation" class="mb-1 block text-sm font-medium">Konfirmasi password baru</label>
                <input type="password" id="profile-password-confirmation" wire:model="password_confirmation" autocomplete="new-password"
                       class="w-full rounded border border-gray-300 px-3 py-2">
            </div>
            <button type="submit" id="profile-password-save" class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Ubah password</button>
        </form>
    </section>
</div>
