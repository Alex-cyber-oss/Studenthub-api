#!/bin/bash

echo "🚀 Déploiement StudentHub API sur Heroku"
echo "========================================"

# Vérifier si Heroku CLI est installé
if ! command -v heroku &> /dev/null; then
    echo "❌ Heroku CLI n'est pas installé."
    echo "Téléchargez-le depuis : https://devcenter.heroku.com/articles/heroku-cli"
    exit 1
fi

# Vérifier si connecté à Heroku
if ! heroku whoami &> /dev/null; then
    echo "❌ Vous n'êtes pas connecté à Heroku."
    echo "Exécutez : heroku login"
    exit 1
fi

# Créer l'app Heroku
echo "📦 Création de l'app Heroku..."
read -p "Nom de votre app Heroku (laissez vide pour auto-générer) : " APP_NAME

if [ -z "$APP_NAME" ]; then
    heroku create studenthub-api-$RANDOM
else
    heroku create $APP_NAME
fi

# Ajouter les buildpacks
echo "🔧 Configuration des buildpacks..."
heroku buildpacks:add heroku/php --app $APP_NAME
heroku buildpacks:add heroku/nodejs --app $APP_NAME

# Configurer les variables d'environnement
echo "⚙️ Configuration des variables d'environnement..."
heroku config:set APP_ENV=production --app $APP_NAME
heroku config:set APP_DEBUG=false --app $APP_NAME
heroku config:set APP_KEY=$(php artisan key:generate --show) --app $APP_NAME
heroku config:set LOG_LEVEL=error --app $APP_NAME

# Déployer
echo "🚀 Déploiement..."
git add .
git commit -m "Deploy to Heroku"
git push heroku main

# Exécuter les migrations
echo "🗄️ Exécution des migrations..."
heroku run php artisan migrate --force --app $APP_NAME

echo ""
echo "✅ Déploiement terminé !"
echo "🌐 Votre API est disponible sur : https://$APP_NAME.herokuapp.com"
echo ""
echo "📝 Pensez à mettre à jour votre frontend avec cette URL !"