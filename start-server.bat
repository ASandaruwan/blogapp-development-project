@echo off
title Blog App - Local Server
echo ========================================================
echo   Starting Blog App Development Server
echo ========================================================
echo.
echo [1] Checking XAMPP PHP...
if not exist "C:\xampp\php\php.exe" (
    echo ERROR: C:\xampp\php\php.exe not found!
    echo Please make sure XAMPP is installed at C:\xampp.
    pause
    exit /b
)

echo [2] Starting server with router at http://localhost:8000 ...
echo [INFO] Clean URLs and API routes enabled via router.php
echo [INFO] Press Ctrl+C in this window anytime to stop the server.
echo.

start http://localhost:8000
"C:\xampp\php\php.exe" -S localhost:8000 -t "%~dp0blog-app" "%~dp0blog-app\router.php"
pause
