@echo off
title AjoNova Queue Worker
echo ========================================================
echo   AjoNova Background Queue Worker
echo   Waiting for emails, OTPs, and background tasks...
echo   (Press Ctrl+C to stop)
echo ========================================================
echo.
php artisan queue:work --tries=3 --timeout=90
pause
