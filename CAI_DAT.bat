@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo ==========================================
echo CAI DAT PHAN MEM QUAN LY CHI TIEU KPI
echo ==========================================
where php >nul 2>nul || (echo [LOI] Chua tim thay PHP trong PATH.& pause & exit /b 1)
where composer >nul 2>nul || (echo [LOI] Chua tim thay Composer trong PATH.& pause & exit /b 1)
if not exist storage\framework\cache mkdir storage\framework\cache
if not exist storage\framework\sessions mkdir storage\framework\sessions
if not exist storage\framework\views mkdir storage\framework\views
if not exist storage\logs mkdir storage\logs
if not exist bootstrap\cache mkdir bootstrap\cache
composer install
if errorlevel 1 (echo [LOI] Composer install that bai.& pause & exit /b 1)
if not exist .env copy .env.example .env
php artisan key:generate --force
php artisan optimize:clear
echo.
echo CAI DAT CODE THANH CONG.
echo Tiep theo hay import file DATABASE_KPI_MANAGER.sql.
pause
