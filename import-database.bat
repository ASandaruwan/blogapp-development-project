@echo off
title Blog App - Database Setup
echo ========================================================
echo   Importing Database Schema to MySQL
echo ========================================================
echo.
echo Make sure MySQL is RUNNING in your XAMPP Control Panel first!
echo.
pause

if not exist "C:\xampp\mysql\bin\mysql.exe" (
    echo ERROR: C:\xampp\mysql\bin\mysql.exe not found.
    pause
    exit /b
)

echo Creating database 'blog_app' if it doesn't exist...
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS blog_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Could not connect to MySQL! 
    echo Please open XAMPP Control Panel and click 'Start' next to MySQL.
    echo.
    pause
    exit /b
)

echo Importing schema.sql...
"C:\xampp\mysql\bin\mysql.exe" -u root blog_app < "%~dp0blog-app\database\schema.sql"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================================
    echo [SUCCESS] Database 'blog_app' created and imported!
    echo ========================================================
) else (
    echo.
    echo [ERROR] Failed to import schema.
)

pause
