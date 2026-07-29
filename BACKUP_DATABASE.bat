@echo off
chcp 65001 >nul
setlocal
cd /d "%~dp0"

set "NO_PAUSE=%~1"
set "DB_HOST=127.0.0.1"
set "DB_PORT=3306"
set "DB_DATABASE=kpi_laravel"
set "DB_USERNAME=root"
set "DB_PASSWORD="
for /f "usebackq tokens=1,* delims==" %%A in (".env") do (
  if "%%A"=="DB_HOST" set "DB_HOST=%%B"
  if "%%A"=="DB_PORT" set "DB_PORT=%%B"
  if "%%A"=="DB_DATABASE" set "DB_DATABASE=%%B"
  if "%%A"=="DB_USERNAME" set "DB_USERNAME=%%B"
  if "%%A"=="DB_PASSWORD" set "DB_PASSWORD=%%B"
)
set "DB_HOST=%DB_HOST:"=%"
set "DB_PORT=%DB_PORT:"=%"
set "DB_DATABASE=%DB_DATABASE:"=%"
set "DB_USERNAME=%DB_USERNAME:"=%"
set "DB_PASSWORD=%DB_PASSWORD:"=%"
set "MYSQL_PWD=%DB_PASSWORD%"
set "MYSQLDUMP_EXE="

for /d %%D in ("C:\laragon\bin\mysql\mysql-*") do (
  if exist "%%~fD\bin\mysqldump.exe" set "MYSQLDUMP_EXE=%%~fD\bin\mysqldump.exe"
)
if not defined MYSQLDUMP_EXE if exist "C:\Program Files\MySQL\MySQL Server 9.7\bin\mysqldump.exe" set "MYSQLDUMP_EXE=C:\Program Files\MySQL\MySQL Server 9.7\bin\mysqldump.exe"
if not defined MYSQLDUMP_EXE if exist "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqldump.exe" set "MYSQLDUMP_EXE=C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqldump.exe"
if not defined MYSQLDUMP_EXE if exist "C:\xampp\mysql\bin\mysqldump.exe" set "MYSQLDUMP_EXE=C:\xampp\mysql\bin\mysqldump.exe"
if not defined MYSQLDUMP_EXE (
  where mysqldump >nul 2>nul && set "MYSQLDUMP_EXE=mysqldump"
)

set "GTID_OPTION="
"%MYSQLDUMP_EXE%" --help 2>nul | findstr /i /c:"set-gtid-purged" >nul
if not errorlevel 1 set "GTID_OPTION=--set-gtid-purged=OFF"

if not defined MYSQLDUMP_EXE (
  echo [LOI] Khong tim thay mysqldump.exe.
  echo Hay khoi dong Laragon/XAMPP hoac them MySQL vao PATH.
  if /i not "%NO_PAUSE%"=="--no-pause" pause
  exit /b 1
)

if not exist "storage\backups" mkdir "storage\backups"
for /f %%I in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set "STAMP=%%I"

set "OUTPUT_FILE=%~dp0storage\backups\%DB_DATABASE%_%STAMP%.sql"
set "TEMP_FILE=%OUTPUT_FILE%.tmp"

echo Dang sao luu database %DB_DATABASE%...
"%MYSQLDUMP_EXE%" --host="%DB_HOST%" --port="%DB_PORT%" --user="%DB_USERNAME%" --default-character-set=utf8mb4 --single-transaction --routines --triggers --events %GTID_OPTION% --databases "%DB_DATABASE%" > "%TEMP_FILE%"
if errorlevel 1 (
  if exist "%TEMP_FILE%" del /q "%TEMP_FILE%"
  echo [LOI] Sao luu database that bai.
  if /i not "%NO_PAUSE%"=="--no-pause" pause
  exit /b 1
)

move /y "%TEMP_FILE%" "%OUTPUT_FILE%" >nul
echo [OK] Da tao ban sao luu:
echo %OUTPUT_FILE%
if /i not "%NO_PAUSE%"=="--no-pause" pause
exit /b 0
