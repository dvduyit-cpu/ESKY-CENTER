@echo off
chcp 65001 >nul
setlocal
cd /d "%~dp0"

set "BACKUP_FILE=%~1"
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
if not defined BACKUP_FILE (
  echo Keo file .sql tha vao PHUC_HOI_DATABASE.bat
  echo hoac chay: PHUC_HOI_DATABASE.bat "duong-dan-file.sql"
  pause
  exit /b 1
)

if not exist "%BACKUP_FILE%" (
  echo [LOI] Khong tim thay file:
  echo %BACKUP_FILE%
  pause
  exit /b 1
)

set "MYSQL_EXE="
for /d %%D in ("C:\laragon\bin\mysql\mysql-*") do (
  if exist "%%~fD\bin\mysql.exe" set "MYSQL_EXE=%%~fD\bin\mysql.exe"
)
if not defined MYSQL_EXE if exist "C:\Program Files\MySQL\MySQL Server 9.7\bin\mysql.exe" set "MYSQL_EXE=C:\Program Files\MySQL\MySQL Server 9.7\bin\mysql.exe"
if not defined MYSQL_EXE if exist "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe" set "MYSQL_EXE=C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe"
if not defined MYSQL_EXE if exist "C:\xampp\mysql\bin\mysql.exe" set "MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe"
if not defined MYSQL_EXE (
  where mysql >nul 2>nul && set "MYSQL_EXE=mysql"
)

if not defined MYSQL_EXE (
  echo [LOI] Khong tim thay mysql.exe.
  pause
  exit /b 1
)

echo =====================================================
echo CANH BAO: DATABASE %DB_DATABASE% HIEN TAI SE BI THAY THE
echo =====================================================
echo File phuc hoi: %BACKUP_FILE%
echo He thong se tu sao luu database hien tai truoc.
set /p "CONFIRM=Nhap PHUC HOI de tiep tuc: "
if /i not "%CONFIRM%"=="PHUC HOI" (
  echo Da huy.
  pause
  exit /b 0
)

call "%~dp0BACKUP_DATABASE.bat" --no-pause
if errorlevel 1 (
  echo [LOI] Khong tao duoc ban sao luu an toan. Da dung phuc hoi.
  pause
  exit /b 1
)

echo Dang phuc hoi database...
"%MYSQL_EXE%" --host="%DB_HOST%" --port="%DB_PORT%" --user="%DB_USERNAME%" --default-character-set=utf8mb4 < "%BACKUP_FILE%"
if errorlevel 1 (
  echo [LOI] Phuc hoi that bai. Ban sao luu truoc phuc hoi van duoc giu.
  pause
  exit /b 1
)

php artisan optimize:clear
echo [OK] Phuc hoi database thanh cong.
pause
exit /b 0
