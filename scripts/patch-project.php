<?php

/**
 * Patch satu kali (aman dijalankan berulang) untuk Tahap 6:
 *  - composer.json  : script `test` (coverage 100% wajib) + `test:quick`
 *  - .gitignore     : abaikan artefak uji / rahasia
 *  - config/app.php : 'force_https' (jika belum ada)
 *  - .env.example   : FORCE_HTTPS=false (jika belum ada)
 *  - hapus ExampleTest bawaan Laravel (tidak cocok lagi dengan aplikasi ini)
 *
 * Jalankan dari root proyek:  php scripts/patch-project.php
 */
$root = dirname(__DIR__);
$log = [];

// ---- composer.json ----
$composerPath = $root.'/composer.json';
$composer = json_decode((string) file_get_contents($composerPath));

if (! $composer instanceof stdClass) {
    fwrite(STDERR, "composer.json tidak valid.\n");
    exit(1);
}

$composer->scripts ??= new stdClass;
$composer->scripts->test = [
    '@php artisan config:clear --ansi',
    '@php vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml --log-junit=junit.xml',
    '@php scripts/check-coverage.php coverage.xml',
];
$composer->scripts->{'test:quick'} = [
    '@php artisan config:clear --ansi',
    '@php artisan test',
];
$composer->config ??= new stdClass;
$composer->config->{'process-timeout'} = 0;

file_put_contents(
    $composerPath,
    json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n"
);
$log[] = 'composer.json: script test + test:quick diatur';

// ---- .gitignore ----
$gitignorePath = $root.'/.gitignore';
$gitignore = is_file($gitignorePath) ? (string) file_get_contents($gitignorePath) : '';
$existing = array_map('trim', explode("\n", $gitignore));
$wanted = [
    '.env',
    '.env.*',
    '!.env.example',
    '/vendor',
    '/node_modules',
    '/public/build',
    '/public/hot',
    '/public/storage',
    '/storage/*.key',
    '/storage/logs',
    '.phpunit.cache',
    '.phpunit.result.cache',
    'coverage.xml',
    'coverage.txt',
    'coverage/',
    'coverage-*',
    'junit.xml',
    'clover.xml',
    'events.txt',
    'phpunit_*.txt',
    'latest-app.zip',
    '*.zip',
    'trivy-*.json',
    'trivy-*.txt',
];
$added = [];
foreach ($wanted as $line) {
    if (! in_array($line, $existing, true)) {
        $added[] = $line;
    }
}
if ($added !== []) {
    $gitignore = rtrim($gitignore)."\n\n# Artefak uji / rahasia (Tahap 6)\n".implode("\n", $added)."\n";
    file_put_contents($gitignorePath, $gitignore);
}
$log[] = '.gitignore: '.count($added).' baris ditambahkan';

// ---- config/app.php : force_https ----
$appConfigPath = $root.'/config/app.php';
$appConfig = (string) file_get_contents($appConfigPath);
if (! str_contains($appConfig, 'force_https')) {
    $patched = preg_replace_callback(
        "/^(\s*)'url' => env\('APP_URL'.*$/m",
        fn ($m) => $m[0]."\n\n".$m[1]."'force_https' => (bool) env('FORCE_HTTPS', false),",
        $appConfig,
        1,
        $count
    );
    if ($count === 1) {
        file_put_contents($appConfigPath, $patched);
        $log[] = "config/app.php: 'force_https' ditambahkan";
    } else {
        $log[] = "PERINGATAN: baris 'url' di config/app.php tidak ditemukan; tambahkan manual: 'force_https' => (bool) env('FORCE_HTTPS', false),";
    }
} else {
    $log[] = "config/app.php: 'force_https' sudah ada";
}

// ---- .env.example ----
$examplePath = $root.'/.env.example';
if (is_file($examplePath)) {
    $example = (string) file_get_contents($examplePath);
    if (! preg_match('/^FORCE_HTTPS=/m', $example)) {
        file_put_contents($examplePath, rtrim($example)."\nFORCE_HTTPS=false\n");
        $log[] = '.env.example: FORCE_HTTPS=false ditambahkan';
    }
}

// ---- hapus ExampleTest bawaan ----
foreach (['tests/Feature/ExampleTest.php', 'tests/Unit/ExampleTest.php'] as $file) {
    if (is_file($root.'/'.$file)) {
        unlink($root.'/'.$file);
        $log[] = "dihapus: {$file}";
    }
}

echo implode("\n", $log)."\nSelesai.\n";
