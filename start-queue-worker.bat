@echo off
REM Queue Worker untuk Email Notifications
REM Jalankan file ini untuk start queue worker

cd /d "%~dp0"
echo Starting Queue Worker for Email Notifications...
echo Queue: emails
echo Timeout: 60 seconds
echo Retry: 3 attempts
echo.
echo Press Ctrl+C to stop the worker
echo.

php artisan queue:work --queue=emails --timeout=60 --tries=3

pause
