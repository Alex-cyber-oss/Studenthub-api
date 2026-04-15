#!/bin/bash

# 🚀 Script d'installation complet StudentHub
# Utilisation : bash install.sh

set -e

echo "=========================================="
echo "📦 Installation StudentHub (Backend)"
echo "=========================================="

# Backend Setup
cd studenthub-api

echo "1️⃣ Installation des dépendances Composer..."
composer install --no-interaction --prefer-dist

echo "2️⃣ Configuration de l'environnement..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✓ .env créé (à configurer)"
else
    echo "✓ .env existe déjà"
fi

echo "3️⃣ Génération de la clé APP..."
php artisan key:generate --force

echo "4️⃣ Exécution des migrations..."
php artisan migrate --force

echo "5️⃣ Optionnel: Génération de données de test"
read -p "Exécuter les seeders? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan db:seed --force
fi

echo ""
echo "=========================================="
echo "📦 Installation StudentHub (Frontend)"
echo "=========================================="

cd ../studenthub-frontend

echo "1️⃣ Installation des dépendances npm..."
npm install

echo "2️⃣ Compilation pour développement..."
npm run build

echo "3️⃣ Copie des fichiers compilés..."
# Les fichiers compilés sont dans dist/
# À copier vers le backend ou servir séparément

echo ""
echo "=========================================="
echo "✅ Installation terminée !"
echo "=========================================="

echo ""
echo "📋 Prochaines étapes :"
echo ""
echo "1. Configurer le fichier .env :"
echo "   nano studenthub-api/.env"
echo ""
echo "2. Pour le développement local :"
echo "   cd studenthub-api"
echo "   php artisan serve"
echo ""
echo "   # Dans une autre console :"
echo "   cd studenthub-frontend"
echo "   npm run dev"
echo ""
echo "3. Application sera accessible à :"
echo "   Frontend: http://localhost:5173"
echo "   Backend: http://localhost:8000"
echo ""
echo "🌐 Pour l'hébergement, voir PWA_REMINDERS_GUIDE.md"
