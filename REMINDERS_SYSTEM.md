# 🔔 Système de Rappels de Tâches - Documentation Backend

## 📋 Vue d'ensemble

Le système de rappels génère automatiquement des notifications planifiées pour chaque tâche créée. Les rappels ont une fréquence croissante qui s'intensifie à l'approche de la deadline.

## 🏗️ Architecture

### Modèles

#### `Task` (App\Models\Task)
- Relation : `hasMany(TaskReminder)`
- Contient les tâches avec titre, description, deadline

#### `TaskReminder` (App\Models\TaskReminder)
- Table : `task_reminders`
- Colonnes principales :
  - `task_id` : Référence à la tâche
  - `reminder_time` : Datetime du rappel
  - `status` : pending|sent|cancelled
  - `time_slot` : 20h30 ou 22h
  - `frequency` : daily

### Services

#### `TaskReminderService` (App\Services\TaskReminderService)

**Méthodes publiques :**

```php
// Générer tous les rappels pour une tâche
TaskReminderService::generateReminders(Task $task)

// Obtenir les rappels en attente (à envoyer maintenant)
TaskReminderService::getPendingReminders()

// Marquer un rappel comme envoyé
TaskReminderService::markAsSent(TaskReminder $reminder)
```

## 🔄 Flux d'exécution

### 1. Création d'une Tâche

```
POST /api/tasks
├─ Valider les données
├─ Créer la tâche
├─ TaskReminderService::generateReminders()
│  ├─ Calculer jours jusqu'à deadline
│  ├─ Pour chaque jour :
│  │  └─ Déterminer les heures (20h30, 22h)
│  └─ Créer les TaskReminder en DB
└─ Retourner la tâche créée
```

### 2. Synchronisation des Rappels (Frontend)

```
Client (toutes les 60 secondes)
├─ GET /api/reminders/pending
├─ Backend cherche les rappels à envoyer CETTE HEURE
├─ Frontend reçoit les rappels
├─ Affiche les notifications
└─ PATCH /api/reminders/{id}/sent (marquer comme envoyé)
```

### 3. Modification d'une Tâche

```
PUT /api/tasks/{id}
├─ Si deadline modifiée :
│  ├─ Supprimer les anciens rappels
│  └─ TaskReminderService::generateReminders() (nouveaux)
└─ Sinon : Ne pas modifier les rappels
```

## 🕐 Logique de Fréquence

```php
// Dans TaskReminderService::getReminderTimes($daysRemaining)

if ($daysRemaining <= 1) {
    // Jour J : 20h30 + 22h
    return [20h30, 22h];
}

if ($daysRemaining <= 3) {
    // 2-3 jours avant : 20h30 + 22h
    return [20h30, 22h];
}

if ($daysRemaining <= 7) {
    // 3-7 jours : 20h30 + 22h
    return [20h30, 22h];
}

// Plus de 7 jours : 20h30 seulement
return [20h30];
```

## 📡 API Complète

### GET /api/reminders/pending
**Récupère les rappels à envoyer maintenant (cette heure)**

Réponse :
```json
[
  {
    "id": 1,
    "task_id": 5,
    "reminder_time": "2026-04-15T20:30:00",
    "status": "pending",
    "time_slot": "20h30",
    "task": {
      "id": 5,
      "title": "Projet de mathématiques",
      "deadline": "2026-04-16T00:00:00",
      "course": {
        "id": 2,
        "title": "Mathématiques"
      }
    }
  }
]
```

### GET /api/reminders/sync
**Endpoint pour la synchronisation client (polling)**

Réponse :
```json
{
  "status": "ok",
  "reminders": [...],
  "count": 2
}
```

### PATCH /api/reminders/{id}/sent
**Marquer un rappel comme envoyé**

Réponse : Le rappel mis à jour

### GET /api/reminders
**Lister tous les rappels de l'utilisateur (paginé)**

Query params :
- `page` : Numéro de page (défaut: 1)
- `per_page` : Résultats par page (défaut: 20)

### GET /api/reminders/stats
**Obtenir les statistiques des rappels**

Réponse :
```json
{
  "pending": 12,
  "sent": 145,
  "cancelled": 3
}
```

## 🗄️ Migration & Installation

### 1. Exécuter la migration

```bash
php artisan migrate
```

Crée la table `task_reminders` avec les colonnes :
- id, task_id, reminder_time, status, time_slot, frequency
- created_at, updated_at

### 2. Vérifier que le TaskReminderController est importé dans api.php

```php
use App\Http\Controllers\TaskReminderController;
```

### 3. Tester manuellement

```bash
# Créer une tâche avec deadline
curl -X POST http://localhost/api/tasks \
  -H "Authorization: Bearer TOKEN" \
  -d '{
    "course_id": 1,
    "title": "Test",
    "deadline": "2026-04-20"
  }'

# Vérifier les rappels créés
curl http://localhost/api/reminders \
  -H "Authorization: Bearer TOKEN"

# Récupérer les rappels en attente
curl http://localhost/api/reminders/pending \
  -H "Authorization: Bearer TOKEN"
```

## ⚙️ Configuration Avancée

### Modifier les heures de rappel

Éditer `TaskReminderService::getReminderTimes()` :

```php
public static function getReminderTimes($daysRemaining)
{
    if ($daysRemaining <= 1) {
        return [
            ['hour' => 18, 'minute' => 0, 'label' => '18h'],    // Modifier ici
            ['hour' => 20, 'minute' => 0, 'label' => '20h'],
        ];
    }
    // ...
}
```

### Modifier la fréquence

La logique est dans `getReminderTimes()`. Actuellement :
- Les seuils sont : 0, 3, 7 jours
- Vous pouvez les changer (ex: 0, 2, 5, 10 jours)

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

## 🐛 Dépannage

### Les rappels ne se créent pas
1. Vérifier que la migration a été exécutée : `php artisan migrate:status`
2. Vérifier qu'aucune erreur dans les logs
3. Vérifier manuellement en DB : `SELECT * FROM task_reminders;`

### Les rappels ne s'envoient pas
1. Vérifier que le frontend appelle `GET /api/reminders/pending`
2. Vérifier que le code appelle `TaskReminderService::markAsSent()`
3. Vérifier les logs : `tail -f storage/logs/laravel.log`

### Performance (si beaucoup de tâches)
1. Ajouter un index sur `reminder_time` et `status` (déjà présent)
2. Implémenter une queue Laravel (artisan commands) pour la génération
3. Ajouter caching des rappels en attente

## 🔐 Sécurité

- Tous les endpoints sont protégés par `auth:sanctum`
- Les utilisateurs ne voient que leurs propres rappels
- Vérification que la tâche appartient au propriétaire du cours

## 📦 Dépendances

- Laravel Framework
- Carbon (pour les dates)
- Aucune dépendance externe supplémentaire

## 🚀 Amélioration futures

- [ ] SMS/Email de rappel (Laravel Notifications)
- [ ] Rappels custom par utilisateur (dans les settings)
- [ ] Algorithme ML pour prédire les deadlines difficiles
- [ ] Intégration avec les calendriers (iCal)
- [ ] Rappels vocaux via Twilio
