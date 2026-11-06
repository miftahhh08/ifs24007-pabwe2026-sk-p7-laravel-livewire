# Menambahkan konfigurasi OpenAI ke .env dan .env.example (tidak menimpa isi lain).
# Jalankan dari ROOT proyek:
#   powershell -ExecutionPolicy Bypass -File .\setup-openai-env.ps1
param([string]$ApiKey = "")

if (-not (Test-Path ".env")) {
    Write-Host "File .env tidak ditemukan. Jalankan skrip ini dari root proyek." -ForegroundColor Red
    exit 1
}

if ($ApiKey -eq "") {
    $ApiKey = Read-Host "Masukkan OPENAI_API_KEY (Enter jika belum punya)"
}

$utf8 = New-Object System.Text.UTF8Encoding($false)

function Set-EnvValue([string]$Path, [string]$Key, [string]$Value, [bool]$Overwrite) {
    if (-not (Test-Path $Path)) { return }

    $text = [IO.File]::ReadAllText($Path)
    $pattern = "(?m)^" + [regex]::Escape($Key) + "=.*$"

    if ($text -match $pattern) {
        if (-not $Overwrite) { return }
        $replacement = ("$Key=$Value").Replace('$', '$$')
        $text = [regex]::Replace($text, $pattern, $replacement)
    } else {
        if ($text.Length -gt 0 -and -not $text.EndsWith("`n")) { $text += "`n" }
        $text += "$Key=$Value`n"
    }

    [IO.File]::WriteAllText($Path, $text, $utf8)
}

# .env : isi API key hanya jika diberikan (key lama tidak ditimpa dengan kosong)
Set-EnvValue ".env" "OPENAI_API_KEY" $ApiKey ($ApiKey -ne "")
Set-EnvValue ".env" "OPENAI_MODEL" "gpt-4o-mini" $false
Set-EnvValue ".env" "OPENAI_BASE_URL" "https://api.openai.com/v1" $false

# .env.example : SELALU tanpa key rahasia (file ini ikut di-commit)
Set-EnvValue ".env.example" "OPENAI_API_KEY" "" $false
Set-EnvValue ".env.example" "OPENAI_MODEL" "gpt-4o-mini" $false
Set-EnvValue ".env.example" "OPENAI_BASE_URL" "https://api.openai.com/v1" $false

Write-Host "Selesai. Menjalankan php artisan config:clear ..." -ForegroundColor Green
php artisan config:clear
