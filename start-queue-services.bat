@echo off
rem Starts the background services the queue system needs on the school PC:
rem   - live updates (Laravel Reverb)
rem   - the queue worker that sends those updates
rem   - the scheduler, which runs the daily database backup
rem The website itself is served by Herd and XAMPP's Apache, which start on their own.
rem
rem To start these automatically when the PC logs in, put a shortcut to this file in the
rem Windows Startup folder (press Win+R, type shell:startup, press Enter).

cd /d "%~dp0"
start "Queue - live updates" /min cmd /c php artisan reverb:start
start "Queue - worker" /min cmd /c php artisan queue:work --tries=1
start "Queue - scheduler" /min cmd /c php artisan schedule:work
