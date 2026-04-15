# 🗄️ Configuration PostgreSQL sur Railway

## 🚀 Étapes pour configurer PostgreSQL

### **Étape 1 : Créer la base de données**
1. **Dans Railway** → Votre projet → **Add Plugin**
2. **Sélectionner :** "PostgreSQL"
3. **Nommer :** `studenthub-db`
4. **Railway crée automatiquement** la DB PostgreSQL

### **Étape 2 : Connecter l'API à la DB**
1. **Dans Railway** → Votre service API → **Variables**
2. **Les variables sont automatiquement créées :**
   ```
   PGHOST=containers-us-west-1.railway.app
   PGPORT=1234
   PGDATABASE=railway
   PGUSER=postgres
   PGPASSWORD=********
   ```

### **Étape 3 : Déployer**
```bash
# Railway détecte automatiquement les variables PG*
# et les injecte dans votre app
git push origin main
```

### **Étape 4 : Exécuter les migrations**
```bash
# Dans Railway → votre service → Terminal
php artisan migrate
```

---

## 🔧 Configuration alternative (si vous voulez rester sur MySQL)

### **Option A : Railway + MySQL**
1. **Add Plugin** → "MySQL" au lieu de PostgreSQL
2. **Variables générées :** `MYSQLHOST`, `MYSQLPORT`, etc.
3. **Modifier `.env.production` :**
```env
DB_CONNECTION=mysql
DB_HOST=${{MYSQLHOST}}
DB_PORT=${{MYSQLPORT}}
DB_DATABASE=${{MYSQLDATABASE}}
DB_USERNAME=${{MYSQLUSER}}
DB_PASSWORD=${{MYSQLPASSWORD}}
```

### **Option B : PlanetScale (gratuit)**
1. **Créer un compte :** https://planetscale.com
2. **Créer une DB MySQL gratuite**
3. **URL de connexion :** `mysql://user:pass@host/db`
4. **Variables dans Railway :**
```env
DB_CONNECTION=mysql
DB_HOST=votre-host.planetscale.net
DB_PORT=3306
DB_DATABASE=votre-db
DB_USERNAME=votre-user
DB_PASSWORD=votre-password
```

---

## ✅ Vérification

### **Tester la connexion DB :**
```bash
# Dans Railway Terminal :
php artisan tinker
# Puis :
DB::select('SELECT version()');
# Devrait afficher la version PostgreSQL
```

### **Vérifier les migrations :**
```bash
php artisan migrate:status
# Devrait montrer toutes les migrations "Ran"
```

---

## 📊 Avantages PostgreSQL

- ✅ **Gratuit** sur Railway
- ✅ **Auto-scaling** avec Railway
- ✅ **Backup automatique**
- ✅ **Compatible** avec toutes les fonctionnalités Laravel
- ✅ **Plus performant** que SQLite pour production

---

## 🆘 Dépannage

### **"Database connection failed"**
- Vérifier que PostgreSQL plugin est ajouté
- Vérifier les variables d'environnement dans Railway

### **"Migration error"**
```bash
# Forcer les migrations
php artisan migrate --force
```

### **"Table doesn't exist"**
```bash
# Reset et re-run
php artisan migrate:fresh
```

---

## 🎯 Résumé

**Avec Railway + PostgreSQL :**
- ✅ DB gratuite et managée
- ✅ Auto-scaling
- ✅ Backup automatique
- ✅ Production-ready

**Votre app sera déployée avec une vraie DB PostgreSQL ! 🚀**