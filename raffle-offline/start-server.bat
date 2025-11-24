@echo off
REM Offline Prize Drawing System - Quick Start Script (Windows)

echo Starting Offline Prize Drawing System...
echo.

REM Check if PHP is installed
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo Error: PHP is not installed
    echo Please install PHP 7.4 or higher
    pause
    exit /b 1
)

REM Check PHP version
for /f "tokens=*" %%i in ('php -r "echo PHP_VERSION;"') do set PHP_VERSION=%%i
echo PHP version: %PHP_VERSION%

REM Check if data files exist
if not exist "data\prizes.json" (
    echo Error: data\prizes.json not found
    pause
    exit /b 1
)

if not exist "data\special_prize_settings.json" (
    echo Error: data\special_prize_settings.json not found
    pause
    exit /b 1
)

echo Data files found
echo.
echo ==========================================
echo   Server starting on http://localhost:8000
echo ==========================================
echo.
echo Main Page:  http://localhost:8000/
echo Admin Page: http://localhost:8000/admin/
echo.
echo Press Ctrl+C to stop the server
echo.

REM Start PHP built-in server
php -S localhost:8000
