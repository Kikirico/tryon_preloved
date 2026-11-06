param([switch]$WebOnly)

$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
if (Test-Path '.runtime/php/php.exe') {
    $php = (Resolve-Path '.runtime/php/php.exe').Path
} else {
    $php = (Get-Command php).Source
}

# Check before starting anything so an existing session is left untouched.
$ports = @(8000)
if (!$WebOnly) { $ports += 8001 }
$listeners = [System.Net.NetworkInformation.IPGlobalProperties]::GetIPGlobalProperties().GetActiveTcpListeners()
foreach ($port in $ports) {
    if ($listeners.Port -contains $port) {
        throw "Port $port sedang digunakan. Hentikan layanan lama lewat Ctrl+C di terminalnya sebelum menjalankan skrip ini."
    }
}

if ($WebOnly) {
    & $php artisan serve --host=127.0.0.1 --port=8000 --tries=1
    return
}

$web = $null
try {
    $web = Start-Process -FilePath $php -ArgumentList 'artisan serve --host=127.0.0.1 --port=8000 --tries=1' -WorkingDirectory $PSScriptRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput '.runtime/web.log' -RedirectStandardError '.runtime/web-error.log'
    $webReady = $false
    for ($attempt = 0; $attempt -lt 30; $attempt++) {
        if ($web.HasExited) { throw 'Website gagal dimulai. Periksa .runtime/web.log dan .runtime/web-error.log.' }
        try {
            $response = Invoke-WebRequest 'http://127.0.0.1:8000' -UseBasicParsing -TimeoutSec 2
            $webReady = $response.StatusCode -eq 200
        } catch { $webReady = $false }
        if ($webReady) { break }
        Start-Sleep -Milliseconds 500
    }
    if (!$webReady) { throw 'Website belum dapat diakses. Periksa .runtime/web.log dan .runtime/web-error.log.' }

    Write-Host 'Website aktif: http://127.0.0.1:8000'
    Write-Host 'Menyalakan AI Fitting, antrean, dan pembersihan foto...'
    Write-Host 'Buka http://127.0.0.1:8000/ai-fitting dan tunggu indikator AI siap.'
    Write-Host 'Biarkan terminal ini terbuka. Tekan Ctrl+C untuk menghentikan website dan AI.'
    & (Join-Path $PSScriptRoot 'start-fashn.ps1')
} finally {
    if ($web -and !$web.HasExited) {
        # Artisan owns a PHP server child; stop only this launcher's process tree.
        & "$env:SystemRoot/System32/taskkill.exe" /PID $web.Id /T /F *> $null
    }
}
