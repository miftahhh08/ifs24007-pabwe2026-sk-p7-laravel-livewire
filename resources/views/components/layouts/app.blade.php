<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 antialiased">
@auth
    @php($authUser = auth()->user())
    <div class="flex min-h-screen">
        <aside class="w-60 shrink-0 bg-slate-900 text-slate-100 flex flex-col">
            <div class="px-5 py-5 text-lg font-semibold border-b border-slate-700">
                {{ config('app.name') }}
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
                @if ($authUser->isAdmin())
                    <a href="{{ url('/admin/users') }}"
                       class="block rounded px-3 py-2 hover:bg-slate-700 {{ request()->is('admin/*') ? 'bg-slate-700' : '' }}">Pengguna</a>
                @else
                    <a href="{{ url('/stocks') }}"
                       class="block rounded px-3 py-2 hover:bg-slate-700 {{ request()->is('stocks*') ? 'bg-slate-700' : '' }}">Stok Barang</a>
                @endif
                <a href="{{ url('/profile') }}"
                   class="block rounded px-3 py-2 hover:bg-slate-700 {{ request()->is('profile') ? 'bg-slate-700' : '' }}">Profil</a>
            </nav>
            <div class="px-4 py-4 border-t border-slate-700 text-sm">
                <div class="flex items-center gap-3 mb-3">
                    @if ($authUser->profilePhotoUrl())
                        <img src="{{ $authUser->profilePhotoUrl() }}" alt="Foto profil" class="h-9 w-9 rounded-full object-cover">
                    @else
                        <div class="h-9 w-9 rounded-full bg-slate-600 flex items-center justify-center font-semibold">
                            {{ strtoupper(substr($authUser->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <div class="truncate font-medium">{{ $authUser->name }}</div>
                        <div class="text-xs text-slate-400">{{ $authUser->role }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" id="logout-submit"
                            class="w-full rounded bg-slate-700 px-3 py-2 text-left hover:bg-slate-600">Keluar</button>
                </form>
            </div>
        </aside>
        <main class="flex-1 p-6 overflow-x-auto">
            {{ $slot }}
        </main>
    </div>
@else
    <main class="min-h-screen flex items-center justify-center p-4">
        {{ $slot }}
    </main>
@endauth
@stack('scripts')
</body>
</html>
