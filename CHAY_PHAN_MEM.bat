@echo off
chcp 65001 >nul
cd /d "%~dp0"
if not exist vendor\autoload.php (
  echo [LOI] Chua cai Composer. Hay chay CAI_DAT.bat truoc.
  pause
  exit /b 1
)
php artisan optimize:clear
start "" http://127.0.0.1:8000
php artisan serve --host=0.0.0.0 --port=8000
pause
