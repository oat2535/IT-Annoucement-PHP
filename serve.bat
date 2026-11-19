@echo off
set PORT=%1
if "%PORT%"=="" set PORT=5897
echo Starting IT Announcement API Server on port %PORT%...
"C:\php\php.exe" "%~dp0artisan" serve --port=%PORT%
