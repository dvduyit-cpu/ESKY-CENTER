@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo ==========================================
echo CAI DAT PHAN MEM QUAN LY CHI TIEU KPI
echo ==========================================
set "PHP_EXE=C:\laragon\bin\php\php-8.4.20-Win32-vs17-x64\php.exe"
set "COMPOSER_PHAR=C:\composer\composer.phar"
if not exist "%PHP_EXE%" (echo [LOI] Khong tim thay PHP Laragon 8.4.20 khong NTS.& pause & exit /b 1)
if not exist "%COMPOSER_PHAR%" (echo [LOI] Khong tim thay C:\composer\composer.phar.& pause & exit /b 1)
if not exist storage\framework\cache mkdir storage\framework\cache
if not exist storage\framework\sessions mkdir storage\framework\sessions
if not exist storage\framework\views mkdir storage\framework\views
if not exist storage\logs mkdir storage\logs
if not exist bootstrap\cache mkdir bootstrap\cache
if not exist .env copy .env.example .env
"%PHP_EXE%" "%COMPOSER_PHAR%" install
if errorlevel 1 (echo [LOI] Composer install that bai.& pause & exit /b 1)
"%PHP_EXE%" artisan key:generate --force
"%PHP_EXE%" artisan optimize:clear
echo.
echo CAI DAT CODE THANH CONG.
echo Tiep theo hay import file DATABASE_KPI_MANAGER.sql.
pause
