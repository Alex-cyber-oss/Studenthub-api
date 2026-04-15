# 🚀 Déploiement StudentHub API

## 📋 Options d'hébergement gratuit

### 1️⃣ **Railway** (Recommandé - Ultra simple)
**Avantages :** Déploiement GitHub, interface moderne, gratuit 512MB RAM

#### Étapes :
1. **Créer un compte :** https://railway.app
2. **Connecter GitHub :** Railway → New Project → Deploy from GitHub
3. **Sélectionner le repo :** `studenthub-api`
4. **Auto-déploiement :** Railway détecte automatiquement Laravel
5. **Variables d'env :**
   - `APP_KEY` : Générer avec `php artisan key:generate --show`
   - `APP_ENV=production`
   - `APP_DEBUG=false`

**URL finale :** `https://studenthub-api.up.railway.app`

---

### 2️⃣ **Heroku** (Classique)
**Avantages :** Mature, documentation abondante

#### Préparation :
```bash
# Installer Heroku CLI
# Windows : https://devcenter.heroku.com/articles/heroku-cli

# Se connecter
heroku login

# Script de déploiement automatique
chmod +x deploy-heroku.sh
./deploy-heroku.sh
```

#### Manuellement :
```bash
# Créer l'app
heroku create studenthub-api

# Configurer
heroku buildpacks:add heroku/php
heroku buildpacks:add heroku/nodejs

# Variables d'environnement
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false
heroku config:set APP_KEY=$(php artisan key:generate --show)

# Déployer
git push heroku main

# Migrations
heroku run php artisan migrate --force
```

---

### 3️⃣ **Render** (Simple)
**Avantages :** Interface claire, gratuit 750h/mois

#### Étapes :
1. **Créer un compte :** https://render.com
2. **New → Web Service**
3. **Connecter GitHub :** Sélectionner `studenthub-api`
4. **Configuration :**
   - **Runtime :** `Docker` ou `Native`
   - **Build Command :** `composer install && php artisan key:generate`
   - **Start Command :** `php artisan serve --host=0.0.0.0 --port=$PORT`
5. **Variables d'env :** Même que Railway

---

### 4️⃣ **DigitalOcean App Platform** (Avec crédit gratuit)
**Avantages :** 200$ gratuit, très performant

#### Étapes :
1. **Créer un compte :** https://digitalocean.com (200$ gratuit)
2. **Apps → Create App**
3. **Source :** GitHub (`studenthub-api`)
4. **Configuration :**
   - **Type :** `Web Service`
   - **Runtime :** `PHP`
   - **Build Command :** `composer install`
   - **Run Command :** `php artisan serve --host=0.0.0.0 --port=$PORT`

---

## 🔧 Configuration commune

### Variables d'environnement (obligatoires) :
```bash
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:... (générer avec php artisan key:generate --show)
LOG_LEVEL=error
DB_CONNECTION=sqlite
```

### Après déploiement :
1. **Vérifier :** `https://votre-app.com/api/test`
2. **Migrations :** Exécuter `php artisan migrate` sur le serveur
3. **Frontend :** Mettre à jour l'URL de l'API dans `axiosConfig.js`

---

## 📊 Comparaison

| Plateforme | Gratuit | Facilité | Performance | Limites |
|------------|---------|----------|-------------|---------|
| **Railway** | 512MB RAM | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | 512MB, 1GB DB |
| **Heroku** | 550h/mois | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | Sleep après 30min |
| **Render** | 750h/mois | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | Sleep après 15min |
| **DigitalOcean** | 200$ crédit | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | Payant après crédit |

---

## 🎯 Recommandation

**Pour débuter : Railway** (le plus simple)
**Pour production : DigitalOcean** (le plus performant)

---

## 🆘 Dépannage

### "Build failed"
- Vérifier que `composer.json` existe
- Vérifier les dépendances PHP

### "Migration error"
- Exécuter manuellement : `heroku run php artisan migrate`

### "APP_KEY missing"
- Générer : `php artisan key:generate --show`
- Ajouter dans les variables d'environnement

### "Database error"
- SQLite est configuré par défaut (pas besoin de DB externe)
- Vérifier que `/database/database.sqlite` existe

---

## 📞 Support

- **Railway :** https://docs.railway.app/
- **Heroku :** https://devcenter.heroku.com/
- **Render :** https://docs.render.com/
- **Laravel :** https://laravel.com/docs/deployment