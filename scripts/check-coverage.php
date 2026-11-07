<?php

/**
 * Gerbang coverage: gagal (exit 1) jika Classes / Methods / Lines di bawah 100%.
 * Membaca laporan Clover dari PHPUnit:  php scripts/check-coverage.php coverage.xml
 */
$file = $argv[1] ?? 'coverage.xml';

if (! is_file($file)) {
    fwrite(STDERR, "Laporan coverage '{$file}' tidak ditemukan.\n");
    fwrite(STDERR, "Pastikan driver coverage aktif (Xdebug dengan xdebug.mode=coverage, atau PCOV).\n");
    exit(1);
}

$xml = simplexml_load_file($file);

if ($xml === false) {
    fwrite(STDERR, "Laporan coverage tidak dapat dibaca.\n");
    exit(1);
}

$project = $xml->project->metrics;

$methods = (int) $project['methods'];
$coveredMethods = (int) $project['coveredmethods'];
$lines = (int) $project['statements'];
$coveredLines = (int) $project['coveredstatements'];

$classes = 0;
$coveredClasses = 0;
$uncovered = [];

foreach ($xml->xpath('//class') ?: [] as $class) {
    $m = (int) $class->metrics['methods'];
    $c = (int) $class->metrics['coveredmethods'];
    $s = (int) $class->metrics['statements'];
    $cs = (int) $class->metrics['coveredstatements'];

    if ($m === 0 && $s === 0) {
        continue; // kelas tanpa kode yang dapat dieksekusi (mis. abstract Controller kosong)
    }

    $classes++;

    if ($c === $m && $cs === $s) {
        $coveredClasses++;
    } else {
        $uncovered[] = (string) $class['name'];
    }
}

$pct = fn (int $covered, int $total): float => $total === 0 ? 100.0 : round($covered / $total * 100, 2);

$rows = [
    'Classes' => [$coveredClasses, $classes],
    'Methods' => [$coveredMethods, $methods],
    'Lines' => [$coveredLines, $lines],
];

$failed = false;
echo "\n=== Gerbang Coverage ===\n";
foreach ($rows as $label => [$covered, $total]) {
    $percent = $pct($covered, $total);
    $ok = $percent >= 100.0;
    $failed = $failed || ! $ok;
    printf("%-8s %6.2f%%  (%d/%d)  %s\n", $label, $percent, $covered, $total, $ok ? 'OK' : 'GAGAL');
}

if ($uncovered !== []) {
    echo "\nKelas belum 100%:\n - ".implode("\n - ", array_unique($uncovered))."\n";
}

if ($failed) {
    fwrite(STDERR, "\nCoverage app/ harus 100%. Build GAGAL.\n");
    exit(1);
}

echo "\nCoverage app/ = 100%. OK.\n";
