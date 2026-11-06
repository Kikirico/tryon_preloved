$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
$env:UV_CACHE_DIR = "$PWD/.runtime/uv-cache"
$env:UV_PYTHON_INSTALL_DIR = "$PWD/.runtime/python"
$env:HF_HOME = "$PWD/.runtime/huggingface"
$env:HF_HUB_DISABLE_XET = '1'
if (!(Test-Path '.runtime/uv/uv.exe')) {
    New-Item -ItemType Directory -Force .runtime/uv | Out-Null
    Invoke-WebRequest https://github.com/astral-sh/uv/releases/latest/download/uv-x86_64-pc-windows-msvc.zip -OutFile .runtime/uv.zip
    Expand-Archive .runtime/uv.zip .runtime/uv -Force
}
function Invoke-Checked([scriptblock]$Action) { & $Action; if ($LASTEXITCODE -ne 0) { throw 'Setup gagal. Periksa pesan di atas, lalu jalankan ulang.' } }
Invoke-Checked { .runtime/uv/uv.exe python install 3.11 }
if (!(Test-Path '.runtime/fashn-env/Scripts/python.exe')) { Invoke-Checked { .runtime/uv/uv.exe venv --python 3.11 .runtime/fashn-env } }
if (!(Test-Path '.runtime/fashn-vton/.git')) { Invoke-Checked { git clone https://github.com/fashn-AI/fashn-vton-1.5.git .runtime/fashn-vton } }
Invoke-Checked { git -C .runtime/fashn-vton checkout 7c0f10af3f91ad4048fe9729c470a13ef905d25a }
Invoke-Checked { .runtime/uv/uv.exe pip install --python .runtime/fashn-env/Scripts/python.exe torch==2.6.0 torchvision==0.21.0 --index-url https://download.pytorch.org/whl/cu124 }
Invoke-Checked { .runtime/uv/uv.exe pip install --python .runtime/fashn-env/Scripts/python.exe -r ai/installed-requirements.txt -e .runtime/fashn-vton }
Invoke-Checked { .runtime/fashn-env/Scripts/python.exe -u .runtime/fashn-vton/scripts/download_weights.py --weights-dir .runtime/fashn-weights }
if (Test-Path '.runtime/php/php.exe') { $php = (Resolve-Path '.runtime/php/php.exe').Path } else { $php = (Get-Command php).Source }
Invoke-Checked { & $php scripts/init-fashn.php }
Invoke-Checked { & $php artisan migrate }
Write-Output 'Selesai. Jalankan .\start.ps1 dan .\start-fashn.ps1 di dua terminal.'
