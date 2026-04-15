@echo off
REM Script d'installation complet StudentHub (Windows)
REM Utilisation : install.bat

echo ==========================================
echo Installation StudentHub (Backend)
echo ==========================================

cd studenthub-api

echo 1/5 Installation des dependances Composer...
call composer install --no-interaction --prefer-dist

echo 2/5 Configuration de l'environnement...
if not exist .env (
    copy .env.example .env
    echo ^> .env cree (a configurer)
) else (
    echo ^> .env existe deja
)

echo 3/5 Generation de la cle APP...
call php artisan key:generate --force

echo 4/5 Execution des migrations...
call php artisan migrate --force

echo.
echo ==========================================
echo Installation StudentHub (Frontend)
echo ==========================================

cd ..\studenthub-frontend

echo 1/3 Installation des dependances npm...
call npm install

echo 2/3 Compilation pour developpement...
call npm run build

echo.
echo ==========================================
echo INSTALLATION TERMINEE
echo ==========================================

echo.
echo Prochaines etapes :
echo.
echo 1. Configurer le fichier .env :
echo    Code studenthub-api\.env
echo.
echo 2. Pour le developpement local :
echo    Terminal 1 : cd studenthub-api ^&^& php artisan serve
echo    Terminal 2 : cd studenthub-frontend ^&^& npm run dev
echo.
echo 3. Application sera accessible a :
echo    Frontend: http://localhost:5173
echo    Backend: http://localhost:8000
echo.
echo Voir PWA_REMINDERS_GUIDE.md pour l'hebergement
echo.
pause
