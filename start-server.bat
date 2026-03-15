@echo off
cd /d "%~dp0public"
echo Starting PHP Development Server...
echo Server will run at http://localhost:8000
echo Press Ctrl+C to stop the server
php -S localhost:8000
