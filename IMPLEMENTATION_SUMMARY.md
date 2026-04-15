# 🎉 StudentHub - PWA + Système de Rappels des Tâches

## ✨ Résumé des Modifications

### 🆕 Fichiers Créés

#### Backend (Laravel API)
| Fichier | Description |
|---------|-------------|
| `app/Models/TaskReminder.php` | Modèle pour les rappels de tâches |
| `app/Services/TaskReminderService.php` | Logique de génération des rappels |
| `app/Http/Controllers/TaskReminderController.php` | API pour récupérer/marquer les rappels |
| `database/migrations/2026_04_15_000000_create_task_reminders_table.php` | Migration pour la table des rappels |
| `REMINDERS_SYSTEM.md` | Documentation complète du système |
| `install.sh` / `install.bat` | Scripts d'installation automatiques |

#### Frontend (React PWA)
| Fichier | Description |
|---------|-------------|
| `public/manifest.json` | Configuration PWA (installation app) |
| `public/service-worker.js` | Service Worker (cache, offline, notifications) |
| `src/services/notificationService.js` | Service pour gérer les notifications |
| `src/components/InstallPrompt.jsx` | Composant "Installer l'app" |
| `src/components/NotificationManager.jsx` | Composant "Activer les rappels" |
| `PWA_REMINDERS_GUIDE.md` | Guide complet PWA + hébergement |

### 📝 Fichiers Modifiés

#### Backend
| Fichier | Changements |
|---------|------------|
| `app/Models/Task.php` | Ajout relation `reminders()` |
| `app/Http/Controllers/TaskController.php` | Appel `TaskReminderService::generateReminders()` à la création/modification |
| `routes/api.php` | Ajout routes pour reminders (`/reminders/*`) |

#### Frontend
| Fichier | Changements |
|---------|------------|
| `index.html` | Service Worker + manifest + permissions notifications |
| `src/components/Dashboard.jsx` | Ajout `InstallPrompt` + `NotificationManager` |

---

## 🚀 Installation Rapide

### Étape 1 : Exécuter les migrations

```bash
cd studenthub-api
php artisan migrate
```

Cela crée la table `task_reminders`.

### Étape 2 : Pas d'autre configuration !

Le système fonctionne immédiatement :
- ✅ Les rappels se créent automatiquement quand vous créez une tâche
- ✅ Les notifications s'affichent automatiquement (si activées)
- ✅ L'app peut être installée depuis le navigateur
- ✅ Fonctionne offline

---

## 🔔 Comment Ça Fonctionne

### Côté Backend

1. **Création d'une tâche** (POST /api/tasks)
   ```
   → TaskController::store()
   → TaskReminderService::generateReminders()
   → Crée tous les rappels jusqu'à la deadline
   ```

2. **Rappels générés**
   - Plus de 7 jours : 1 rappel à 20h30 chaque jour
   - 3-7 jours : 2 rappels (20h30 + 22h) chaque jour
   - 0-3 jours : 2 rappels (20h30 + 22h) chaque jour

3. **API de rappels**
   ```
   GET /api/reminders/pending  → Rappels à envoyer maintenant
   PATCH /api/reminders/{id}/sent  → Marquer comme envoyé
   ```

### Côté Frontend

1. **Service Worker** enregistré au démarrage
   - Cache les fichiers pour offline
   - Reçoit les notifications push

2. **NotificationManager** demande la permission
   - Lance une synchronisation toutes les 60 secondes
   - Récupère les rappels en attente
   - Affiche les notifications

3. **InstallPrompt** propose d'installer l'app
   - Fonctionne sur mobile (Android/iOS)
   - Fonctionne sur desktop (Windows/Mac/Linux)

---

## 📱 Utilisation (Pour l'Utilisateur)

### Installer l'App

1. Ouvrir StudentHub dans le navigateur
2. Voir le bouton/prompt "Installer l'app"
3. Confirmer l'installation
4. L'app apparaît sur l'écran d'accueil ! 📲

### Activer les Rappels

1. Aller au **Tableau de bord**
2. Cliquer sur **"Activer les rappels"**
3. Autoriser les notifications
4. ✅ C'est activé ! Les rappels arriveront automatiquement

### Créer une Tâche avec Rappels

1. Créer une tâche avec une deadline
2. **Automtiquement**, les rappels sont créés
3. À chaque heure prévue, une notification arrive
4. Cliquer pour voir la tâche

---

## 🌐 Hébergement

Voir [PWA_REMINDERS_GUIDE.md](studenthub-frontend/PWA_REMINDERS_GUIDE.md) pour :
- Heroku (gratuit/facile)
- DigitalOcean (recommandé, $5-10/mois)
- Vercel + Railway (modern, serverless)
- Configuration complète étape par étape

**Résumé :**
- Besoin d'une base de données (PostgreSQL/MySQL)
- Besoin de PHP 8.1+
- HTTPS obligatoire pour la PWA
- Estimé : 5-10€/mois en hébergement

---

## ✅ Checklist Quick Start

- [ ] Exécuter `php artisan migrate`
- [ ] Créer une tâche avec deadline pour tester
- [ ] Vérifier que `task_reminders` contient des lignes
- [ ] Sur le dashboard, cliquer "Activer les rappels"
- [ ] Vérifier que les notifications fonctionnent
- [ ] Tester l'installation de l'app (navigateur → menu)

---

## 🔧 Configuration Avancée

### Modifier les heures de rappel

Éditer `app/Services/TaskReminderService.php` :

```php
private static function getReminderTimes($daysRemaining)
{
    if ($daysRemaining <= 1) {
        return [
            ['hour' => 20, 'minute' => 30, 'label' => '20h30'],
            ['hour' => 22, 'minute' => 0, 'label' => '22h'],
        ];
    }
    // ...
}
```

### Modifier la fréquence

Changer les seuils (0, 3, 7 jours) à vos valeurs :

```php
// Exemple : 0, 2, 5, 10 jours
if ($daysRemaining <= 2) {
    return [20h30, 22h];
}
```

### Ajouter plus de rappels par jour

```php
if ($daysRemaining <= 3) {
    return [
        ['hour' => 14, 'minute' => 0, 'label' => '14h'],
        ['hour' => 18, 'minute' => 0, 'label' => '18h'],
        ['hour' => 20, 'minute' => 30, 'label' => '20h30'],
        ['hour' => 22, 'minute' => 0, 'label' => '22h'],
    ];
}
```

---

## 🆘 Troubleshooting

| Problème | Solution |
|----------|----------|
| Service Worker non enregistré | Doit être HTTPS (ou localhost). Vérifier que `public/service-worker.js` existe |
| Notifications non reçues | Vérifier permissions navigateur. Vérifier console (F12) |
| Rappels non créés | Exécuter `php artisan migrate`. Vérifier `task_reminders` en DB |
| App n'installe pas | Doit être HTTPS. Vérifier `manifest.json`. Vérifier icônes dans `/public/` |

---

## 📚 Documentation Complète

- **Backend** : [REMINDERS_SYSTEM.md](studenthub-api/REMINDERS_SYSTEM.md)
- **Frontend** : [PWA_REMINDERS_GUIDE.md](studenthub-frontend/PWA_REMINDERS_GUIDE.md)
- **Laravel** : https://laravel.com/docs
- **PWA** : https://web.dev/progressive-web-apps/

---

## 🎯 Fonctionnalités Futures Possibles

- SMS/Email de rappel
- Web Push (au lieu de notifications locales seulement)
- Rappels custom par utilisateur
- Intégration calendrier (Google Calendar, Outlook)
- Rappels vocaux via Twilio
- Interface admin pour gérer les rappels

---

**Bon courage pour l'hébergement ! 🚀**
