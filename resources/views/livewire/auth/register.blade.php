<div class="w-full max-w-md rounded-lg bg-white p-8 shadow">
    <h1 class="mb-6 text-2xl font-semibold">Daftar</h1>

    <form wire:submit="register" class="space-y-4">
        <div>
            <label for="register-name" class="mb-1 block text-sm font-medium">Nama</label>
            <input type="text" id="register-name" wire:model="name"
                   class="w-full rounded border border-gray-300 px-3 py-2">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="register-username" class="mb-1 block text-sm font-medium">Username</label>
            <input type="text" id="register-username" wire:model="username"
                   class="w-full rounded border border-gray-300 px-3 py-2">
            @error('username') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="register-email" class="mb-1 block text-sm font-medium">Email</label>
            <input type="email" id="register-email" wire:model="email"
                   class="w-full rounded border border-gray-300 px-3 py-2">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="register-password" class="mb-1 block text-sm font-medium">Password</label>
            <input type="password" id="register-password" wire:model="password"
                   class="w-full rounded border border-gray-300 px-3 py-2">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="register-password-confirmation" class="mb-1 block text-sm font-medium">Konfirmasi Password</label>
            <input type="password" id="register-password-confirmation" wire:model="password_confirmation"
                   class="w-full rounded border border-gray-300 px-3 py-2">
        </div>

        <button type="submit" id="register-submit"
                class="w-full rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">Daftar</button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Sudah punya akun? <a href="{{ url('/login') }}" class="text-blue-600 hover:underline">Masuk</a>
    </p>
</div>
