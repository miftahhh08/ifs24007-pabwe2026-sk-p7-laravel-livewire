<?php

// Aset Livewire dimuat manual lewat @livewireStyles (head) dan
// @livewireScripts (akhir body) agar skripnya tidak memblokir render pertama.
return [
    'inject_assets' => false,
];
