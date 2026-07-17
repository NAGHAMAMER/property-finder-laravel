param(
    [int]$Port = 8000,
    [switch]$NoServe
)

$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

function Stop-WithMessage([string]$Message) {
    Write-Host "`n$Message" -ForegroundColor Red
    exit 1
}

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Stop-WithMessage 'PHP غير موجود في PATH. استخدمي Terminal الخاص بـ XAMPP/Laragon أو أضيفي php.exe إلى PATH.'
}

$modules = (php -m) -join "`n"
$required = @('openssl', 'fileinfo', 'pdo_mysql', 'mbstring')
$missing = @()
foreach ($module in $required) {
    if ($modules -notmatch "(?im)^$([regex]::Escape($module))$") { $missing += $module }
}

if ($missing.Count -gt 0) {
    Write-Host "امتدادات PHP الناقصة: $($missing -join ', ')" -ForegroundColor Yellow
    Write-Host 'فعّلي extension=pdo_mysql وextension=mbstring وextension=fileinfo من php.ini ثم أعيدي فتح PowerShell.' -ForegroundColor Cyan
    exit 1
}

if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
}

New-Item -ItemType Directory -Force 'storage/framework/views' | Out-Null
New-Item -ItemType Directory -Force 'storage/framework/sessions' | Out-Null
New-Item -ItemType Directory -Force 'storage/framework/cache/data' | Out-Null
New-Item -ItemType Directory -Force 'storage/logs' | Out-Null
New-Item -ItemType Directory -Force 'bootstrap/cache' | Out-Null

$envText = Get-Content '.env' -Raw
if ($envText -match '(?m)^APP_URL=') {
    $envText = [regex]::Replace($envText, '(?m)^APP_URL=.*$', "APP_URL=http://127.0.0.1:$Port")
} else {
    $envText += "`nAPP_URL=http://127.0.0.1:$Port`n"
}
Set-Content '.env' $envText -Encoding UTF8

if ((Get-Content '.env' -Raw) -match '(?m)^APP_KEY=\s*$') {
    php artisan key:generate --force
}

Write-Host 'تأكدي أن MySQL يعمل وأن قاعدة property_finder موجودة وإعدادات DB في .env صحيحة.' -ForegroundColor Yellow
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force

try { php artisan storage:link | Out-Host } catch { Write-Host 'رابط storage موجود مسبقًا أو تعذر إنشاؤه.' -ForegroundColor Yellow }

Write-Host "`nتم تجهيز المشروع بنجاح." -ForegroundColor Green
Write-Host "المستخدم: http://127.0.0.1:$Port/login" -ForegroundColor Cyan
Write-Host "الأدمن:    http://127.0.0.1:$Port/admin/login" -ForegroundColor Cyan

if (-not $NoServe) {
    php artisan serve --host=127.0.0.1 --port=$Port
}
