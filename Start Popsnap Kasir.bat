@echo off
title Popsnap POS
start "Laravel" cmd /k "php artisan serve"
start "Vite" cmd /k "npm run dev"
echo.
echo Popsnap POS berjalan!
echo Laravel : http://127.0.0.1:8000
echo Vite    : http://localhost:5173
echo.