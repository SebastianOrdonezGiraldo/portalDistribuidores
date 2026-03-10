param(
    [switch]$RunMigrations
)

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path

if ($RunMigrations) {
    Write-Host "Running pending migrations..."
    powershell -ExecutionPolicy Bypass -File "$projectRoot\run-artisan.ps1" migrate
}

Write-Host "Starting Laravel server..."
Start-Process powershell -ArgumentList @(
    '-NoExit',
    '-ExecutionPolicy', 'Bypass',
    '-File', "$projectRoot\run-artisan.ps1",
    'serve'
) -WorkingDirectory $projectRoot

Write-Host "Starting queue worker..."
Start-Process powershell -ArgumentList @(
    '-NoExit',
    '-ExecutionPolicy', 'Bypass',
    '-File', "$projectRoot\run-artisan.ps1",
    'queue:work'
) -WorkingDirectory $projectRoot

Write-Host "Portal started. Two PowerShell windows were opened (server and queue worker)."
