@echo off
echo ========================================
echo   INICIANDO AMBIENTE DE TRABALHO
echo ========================================

echo Copiando configuracao para trabalho...
copy ".env.trabalho" ".env" >nul 2>&1

echo Verificando configuracao...
php config-unified.php

echo.
echo Iniciando servidores...
echo.

echo [1/2] Iniciando Frontend (porta 3000)...
start "Frontend" cmd /k "cd frontend && php -S localhost:3000"

echo [2/2] Iniciando Backend (porta 8000)...
start "Backend" cmd /k "cd backend && php -S localhost:8000 router.php"

echo.
echo ========================================
echo   AMBIENTE DE TRABALHO INICIADO!
echo ========================================
echo Frontend: http://localhost:3000
echo Backend:  http://localhost:8000
echo ========================================

pause