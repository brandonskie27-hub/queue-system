<#
    Sets up the PHINMA UPANG queue system on a school Windows PC.

    Before running: install XAMPP (to C:\xampp) and Herd, then start Apache and MySQL in the
    XAMPP Control Panel. Run from the USB kit folder:  right-click this file > Run with PowerShell
    (or: powershell -ExecutionPolicy Bypass -File setup-school-pc.ps1)

    What it does:
      1. Checks that Herd's PHP (8.3 or newer) and XAMPP are installed.
      2. Copies the app to %USERPROFILE%\Herd\queue-system (Herd serves it as queue-system.test).
      3. Configures XAMPP's Apache to serve the app on port 8000 for phones (backs up httpd.conf).
      4. Creates the queue_system database and imports the data from the kit.
#>
param(
    [string] $XamppPath = 'C:\xampp',
    [string] $KitPath = $PSScriptRoot,
    [string] $DatabaseName = 'queue_system',
    [string] $AppTarget = (Join-Path $env:USERPROFILE 'Herd\queue-system'),
    [switch] $SkipCopy
)

$ErrorActionPreference = 'Stop'

function Step($text) { Write-Host "`n== $text" -ForegroundColor Green }
function Fail($text) { Write-Host "`nSTOPPED: $text" -ForegroundColor Red; exit 1 }

# Runs an external program and returns everything it printed as text. Windows PowerShell treats
# any output on a program's error stream as a failure (Apache even prints "Syntax OK" there), so
# that is relaxed for the call; callers check $LASTEXITCODE instead.
function Run([scriptblock] $command) {
    $previous = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try { (& $command 2>&1 | ForEach-Object { "$_" }) -join "`n" } finally { $ErrorActionPreference = $previous }
}

$appSource = Join-Path $KitPath 'queue-system'
$sqlFile = Join-Path $KitPath 'database\queue_system.sql'
$apacheConf = Join-Path $KitPath 'apache\httpd-queue-system.conf'

# 1. Prerequisites -------------------------------------------------------------------------
Step 'Checking what is installed'

$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) { Fail 'PHP was not found. Install Herd, then open a NEW PowerShell window and run this again.' }
$phpVersion = Run { php -r 'echo PHP_VERSION;' }
if ([version]$phpVersion -lt [version]'8.3') { Fail "PHP $phpVersion found, but 8.3 or newer is needed. Make sure Herd's PHP comes first (reinstall Herd or restart the PC)." }
Write-Host "PHP $phpVersion OK ($($php.Source))"

foreach ($file in "$XamppPath\apache\conf\httpd.conf", "$XamppPath\mysql\bin\mysql.exe") {
    if (-not (Test-Path $file)) { Fail "XAMPP not found at $XamppPath ($file is missing). Pass -XamppPath if it is installed elsewhere." }
}
Write-Host "XAMPP OK ($XamppPath)"

foreach ($file in $appSource, $sqlFile, $apacheConf) {
    if (-not (Test-Path $file)) { Fail "The kit is incomplete: $file is missing." }
}

# 2. App files ------------------------------------------------------------------------------
if (-not $SkipCopy) {
    Step "Copying the app to $AppTarget"
    if (Test-Path $AppTarget) { Fail "$AppTarget already exists. Delete or rename it first, or run with -SkipCopy to keep it." }
    New-Item -ItemType Directory -Force (Split-Path $AppTarget) | Out-Null
    robocopy $appSource $AppTarget /E /NFL /NDL /NJH /NJS /NP | Out-Null
    if ($LASTEXITCODE -ge 8) { Fail "Copying the app failed (robocopy code $LASTEXITCODE)." }
    Write-Host 'Copied.'
}

# 3. Apache -------------------------------------------------------------------------------
Step 'Configuring Apache (port 8000)'
$httpdConf = "$XamppPath\apache\conf\httpd.conf"
$backup = "$httpdConf.before-queue-system"
if (-not (Test-Path $backup)) { Copy-Item $httpdConf $backup; Write-Host "Backup saved: $backup" }

Copy-Item $apacheConf "$XamppPath\apache\conf\extra\httpd-queue-system.conf" -Force

$conf = [IO.File]::ReadAllText($httpdConf)
$conf = $conf.Replace('#LoadModule proxy_http_module', 'LoadModule proxy_http_module')
$conf = $conf.Replace('#LoadModule proxy_wstunnel_module', 'LoadModule proxy_wstunnel_module')
# Herd's own web server uses port 80 on this PC, so XAMPP's Apache only listens on 8000.
$conf = [regex]::Replace($conf, '(?m)^Listen 80\s*$', '#Listen 80  (disabled for the queue system; Herd uses port 80)')
$conf = [regex]::Replace($conf, '(?m)^Include conf/extra/httpd-ssl.conf\s*$', '#Include conf/extra/httpd-ssl.conf  (disabled for the queue system)')
if ($conf -notmatch 'httpd-queue-system\.conf') {
    $conf = $conf.TrimEnd() + "`r`n`r`n# PHINMA UPANG queue system: serves the app on port 8000`r`nInclude conf/extra/httpd-queue-system.conf`r`n"
}
[IO.File]::WriteAllText($httpdConf, $conf)

$test = Run { & "$XamppPath\apache\bin\httpd.exe" -t -f $httpdConf }
if ($test -notmatch 'Syntax OK') { Fail "Apache rejected the configuration:`n$test`nRestore $backup to undo." }
Write-Host 'Apache configuration OK.'

# 4. Database ------------------------------------------------------------------------------
Step "Creating the $DatabaseName database"
$mysql = "$XamppPath\mysql\bin\mysql.exe"
$out = Run { & $mysql -u root -h 127.0.0.1 -e "SELECT 1" }
if ($LASTEXITCODE -ne 0) { Fail "Cannot reach MySQL. Start MySQL in the XAMPP Control Panel and run this again.`n$out" }

$exists = Run { & $mysql -u root -h 127.0.0.1 -N -e "SHOW DATABASES LIKE '$DatabaseName'" }
if ($exists.Trim()) { Fail "A database named $DatabaseName already exists. Drop it in phpMyAdmin first if you want a fresh import." }

$out = Run { & $mysql -u root -h 127.0.0.1 -e "CREATE DATABASE ``$DatabaseName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" }
if ($LASTEXITCODE -ne 0) { Fail "Creating the database failed:`n$out" }
$out = Run { & $mysql -u root -h 127.0.0.1 $DatabaseName -e "source $($sqlFile.Replace('\', '/'))" }
if ($LASTEXITCODE -ne 0) { Fail "Importing the data failed:`n$out" }
Write-Host 'Data imported.'

# 5. App settings -------------------------------------------------------------------------
Step 'Finishing the app settings'
Push-Location $AppTarget
try {
    $dotenv = [IO.File]::ReadAllText("$AppTarget\.env")
    $dotenv = [regex]::Replace($dotenv, '(?m)^DB_DATABASE=.*$', "DB_DATABASE=$DatabaseName")
    $dotenv = [regex]::Replace($dotenv, '(?m)^MYSQLDUMP_PATH=.*$', "MYSQLDUMP_PATH=`"$($XamppPath.Replace('\', '/'))/mysql/bin/mysqldump.exe`"")
    [IO.File]::WriteAllText("$AppTarget\.env", $dotenv)

    $out = Run { php artisan migrate --force }
    Write-Host $out
    if ($LASTEXITCODE -ne 0) { Fail 'Updating the database tables failed (see above).' }
    $null = Run { php artisan optimize:clear }
} finally {
    Pop-Location
}

# The adapter with a default gateway is the real network (not a virtual one like VirtualBox's).
$ip = (Get-NetIPConfiguration | Where-Object { $_.IPv4DefaultGateway -and $_.NetAdapter.Status -eq 'Up' } | Select-Object -First 1).IPv4Address.IPAddress
if (-not $ip) { $ip = '<this PC''s IP, see ipconfig>' }

Write-Host "`nDone." -ForegroundColor Green
Write-Host @"

Next:
  1. In the XAMPP Control Panel, Stop and Start Apache.
  2. Double-click $AppTarget\start-queue-services.bat (leave its windows open).
  3. On this PC open http://$($ip):8000 , then try it from a phone on the same Wi-Fi.
  4. Print the poster from http://$($ip):8000/poster
"@
