@echo off
chcp 65001 >nul
cd /d "%~dp0"
set "PHP_EXE="
for /d %%D in ("C:\laragon\bin\php\php-*") do (
  echo %%~nxD | findstr /i /c:"-nts-" >nul
  if errorlevel 1 if exist "%%~fD\php.exe" set "PHP_EXE=%%~fD\php.exe"
)
if not defined PHP_EXE (
  echo [LOI] Khong tim thay PHP Laragon khong NTS.
  echo Hay cai PHP 8.2 tro len trong Laragon.
  pause
  exit /b 1
)
if not exist vendor\autoload.php (
  echo [LOI] Chua cai Composer. Hay chay CAI_DAT.bat truoc.
  pause
  exit /b 1
)
powershell -NoProfile -Command "if (Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue) { exit 0 } else { exit 1 }"
if not errorlevel 1 (
  echo Phan mem dang chay tai http://127.0.0.1:8000
  start "" http://127.0.0.1:8000
  exit /b 0
)
"%PHP_EXE%" artisan optimize:clear
if errorlevel 1 (
  echo [LOI] Khong the khoi tao Laravel. Kiem tra MySQL Laragon va file .env.
  pause
  exit /b 1
)
start "" http://127.0.0.1:8000
"%PHP_EXE%" artisan serve --host=127.0.0.1 --port=8000
echo.
echo Dang sao luu database truoc khi thoat...
call XUAT_DATABASE.bat --no-pause
pause
