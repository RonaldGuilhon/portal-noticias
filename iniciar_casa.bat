@echo off
echo ========================================
echo   INICIANDO AMBIENTE DE CASA
echo ========================================

echo Copiando configuracao para casa...
copy ".env.casa" ".env" >nul 2>&1

echo Verificando configuracao...
php config-unified.php

echo.
echo Iniciando servidores...
echo.

echo [1/2] Iniciando Frontend (porta 8000)...
start "Frontend" cmd /k "cd frontend && php -S localhost:8000"

echo [2/2] Iniciando Backend (porta 8080)...
start "Backend" cmd /k "cd backend && php -S localhost:8080 router.php"

echo.
echo ========================================
echo   AMBIENTE DE CASA INICIADO!
echo ========================================
echo Frontend: http://localhost:8000
echo Backend:  http://localhost:8080
echo ========================================

pause