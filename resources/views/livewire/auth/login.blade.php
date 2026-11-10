<div class="w-full max-w-md rounded-lg bg-white p-8 shadow">
    <h1 class="mb-1 text-2xl font-semibold">Masuk</h1>
    <p class="mb-6 text-sm text-gray-500">Kelola stok barang Anda.</p>

    @if (session('status'))
        <div class="mb-4 rounded bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <form wire:submit="authenticate" class="space-y-4">
        <div>
            <label for="login-username" class="mb-1 block text-sm font-medium">Username atau Email</label>
            <input type="text" id="login-username" name="login" wire:model="login" autocomplete="username"
                   class="w-full rounded border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            @error('login') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="login-password" class="mb-1 block text-sm font-medium">Password</label>
            <input type="password" id="login-password" name="password" wire:model="password" autocomplete="current-password"
                   class="w-full rounded border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="remember" id="login-remember"> Ingat saya
        </label>

        <button type="submit" id="login-submit"
                class="w-full rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">Masuk</button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Belum punya akun? <a href="{{ url('/register') }}" class="font-medium text-blue-700 underline hover:no-underline">Daftar</a>
    </p>
</div>
