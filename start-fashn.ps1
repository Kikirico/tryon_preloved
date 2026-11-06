$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$python = Join-Path $PSScriptRoot '.runtime/fashn-env/Scripts/python.exe'
if (!(Test-Path $python)) { throw 'Python FASHN belum tersedia. Lihat FASHN_SETUP.md.' }
if (!(Test-Path '.runtime/fashn-service.key')) { throw 'Kunci lokal belum tersedia. Jalankan scripts/init-fashn.php dengan PHP.' }
$listeners = [System.Net.NetworkInformation.IPGlobalProperties]::GetIPGlobalProperties().GetActiveTcpListeners()
if ($listeners.Port -contains 8001) { throw 'Port AI 8001 sedang digunakan. Hentikan layanan AI lama lewat Ctrl+C di terminalnya terlebih dahulu.' }
if (Test-Path '.runtime/php/php.exe') { $php = (Resolve-Path '.runtime/php/php.exe').Path } else { $php = (Get-Command php).Source }
$env:HF_HOME = Join-Path $PSScriptRoot '.runtime/huggingface'
$worker = $null
$cleaner = $null
try {
    $worker = Start-Process -FilePath $php -ArgumentList 'artisan queue:work fashn --queue=fashn --sleep=2 --tries=1 --timeout=660' -WorkingDirectory $PSScriptRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput '.runtime/fashn-worker.log' -RedirectStandardError '.runtime/fashn-worker-error.log'
    $cleaner = Start-Process -FilePath $php -ArgumentList 'artisan schedule:work' -WorkingDirectory $PSScriptRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput '.runtime/fashn-schedule.log' -RedirectStandardError '.runtime/fashn-schedule-error.log'
    & $python -m uvicorn server:app --app-dir ai --host 127.0.0.1 --port 8001 --workers 1
} finally {
    foreach ($service in @($worker, $cleaner)) {
        if ($service -and !$service.HasExited) {
            & "$env:SystemRoot/System32/taskkill.exe" /PID $service.Id /T /F *> $null
        }
    }
}
