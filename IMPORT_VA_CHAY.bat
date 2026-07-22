@echo off
chcp 65001 >nul
setlocal
cd /d "%~dp0"

set "MYSQL_EXE="
for /d %%D in ("C:\laragon\bin\mysql\mysql-*") do (
  if exist "%%~fD\bin\mysql.exe" set "MYSQL_EXE=%%~fD\bin\mysql.exe"
)

if not defined MYSQL_EXE (
  echo [LOI] Khong tim thay mysql.exe cua Laragon.
  echo Hay cai va khoi dong Laragon truoc.
  pause
  exit /b 1
)

if not exist "%~dp0database\demo.sql" (
  echo [LOI] Khong tim thay database\demo.sql.
  echo Hay chay XUAT_DATABASE.bat tren may cu truoc khi sao chep.
  pause
  exit /b 1
)

echo ==========================================
echo IMPORT DATABASE KPI VAO MYSQL LARAGON
echo ==========================================
echo Chu y: database kpi_laravel hien tai se bi thay the.
echo Nhan Y de tiep tuc, phim khac de huy.
set /p "CONFIRM=Lua chon: "
if /i not "%CONFIRM%"=="Y" (
  echo Da huy import.
  pause
  exit /b 0
)

echo Dang import database\demo.sql...
"%MYSQL_EXE%" -u root --default-character-set=utf8mb4 < "%~dp0database\demo.sql"
if errorlevel 1 (
  echo [LOI] Import that bai. Kiem tra MySQL Laragon hoac mat khau root.
  pause
  exit /b 1
)

echo [OK] Import database thanh cong.
echo Dang khoi dong phan mem...
endlocal
call "%~dp0CHAY_PHAN_MEM.bat"
