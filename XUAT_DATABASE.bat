@echo off
chcp 65001 >nul
setlocal
cd /d "%~dp0"
set "NO_PAUSE=%~1"

set "MYSQLDUMP_EXE="
for /d %%D in ("C:\laragon\bin\mysql\mysql-*") do (
  if exist "%%~fD\bin\mysqldump.exe" set "MYSQLDUMP_EXE=%%~fD\bin\mysqldump.exe"
)

if not defined MYSQLDUMP_EXE (
  echo [LOI] Khong tim thay mysqldump.exe cua Laragon.
  if /i not "%NO_PAUSE%"=="--no-pause" pause
  exit /b 1
)

if not exist database mkdir database
set "OUTPUT_FILE=%~dp0database\demo.sql"
set "TEMP_FILE=%~dp0database\demo.sql.tmp"

echo Dang xuat database kpi_laravel...
"%MYSQLDUMP_EXE%" -u root --default-character-set=utf8mb4 --single-transaction --routines --triggers --events --databases kpi_laravel > "%TEMP_FILE%"
if errorlevel 1 (
  if exist "%TEMP_FILE%" del /q "%TEMP_FILE%"
  echo [LOI] Xuat database that bai. File demo.sql cu duoc giu nguyen.
  if /i not "%NO_PAUSE%"=="--no-pause" pause
  exit /b 1
)

move /y "%TEMP_FILE%" "%OUTPUT_FILE%" >nul
echo [OK] Da luu database vao:
echo %OUTPUT_FILE%
if /i not "%NO_PAUSE%"=="--no-pause" pause
exit /b 0
