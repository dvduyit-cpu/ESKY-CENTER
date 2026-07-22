@echo off
chcp 65001 >nul
cd /d "%~dp0"
set MYSQL_USER=root
set MYSQL_PASSWORD=
set MYSQL_EXE=
if exist "C:\xampp\mysql\bin\mysql.exe" set MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe
if exist "C:\Program Files\MySQL\MySQL Server 9.7\bin\mysql.exe" set MYSQL_EXE=C:\Program Files\MySQL\MySQL Server 9.7\bin\mysql.exe
if exist "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe" set MYSQL_EXE=C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe
if exist "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" set MYSQL_EXE=C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe
if "%MYSQL_EXE%"=="" (
  where mysql >nul 2>nul && set MYSQL_EXE=mysql
)
if "%MYSQL_EXE%"=="" (
  echo [LOI] Khong tim thay mysql.exe. Hay import DATABASE_KPI_MANAGER.sql bang phpMyAdmin.
  pause
  exit /b 1
)
echo Dang import database...
if "%MYSQL_PASSWORD%"=="" (
  "%MYSQL_EXE%" -u %MYSQL_USER% < DATABASE_KPI_MANAGER.sql
) else (
  "%MYSQL_EXE%" -u %MYSQL_USER% -p%MYSQL_PASSWORD% < DATABASE_KPI_MANAGER.sql
)
if errorlevel 1 (echo [LOI] Import database that bai.& pause & exit /b 1)
echo IMPORT DATABASE THANH CONG.
echo Tai khoan: admin@kpi.local / 12345678
pause
