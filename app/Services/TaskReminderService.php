<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskReminder;
use Carbon\Carbon;

class TaskReminderService
{
    /**
     * Générer automatiquement les rappels pour une tâche
     */
    public static function generateReminders(Task $task)
    {
        // Supprimer les rappels existants
        $task->reminders()->delete();

        $today = Carbon::now()->startOfDay();
        $deadline = Carbon::parse($task->deadline)->startOfDay();
        
        // Ne pas créer de rappels si la deadline est passée
        if ($deadline->isPast()) {
            return;
        }

        $daysUntilDeadline = $today->diffInDays($deadline);

        // Générer les rappels pour chaque jour jusqu'à la deadline
        $currentDate = $today->copy();

        while ($currentDate <= $deadline) {
            $daysRemaining = $deadline->diffInDays($currentDate);

            // Déterminer les heures de notification selon la proximité de la deadline
            $times = self::getReminderTimes($daysRemaining);

            foreach ($times as $time) {
                $reminderTime = $currentDate->copy()
                    ->setHour($time['hour'])
                    ->setMinute($time['minute'])
                    ->setSecond(0);

                // Ne créer le rappel que si c'est dans le futur
                if ($reminderTime->isFuture()) {
                    TaskReminder::create([
                        'task_id' => $task->id,
                        'reminder_time' => $reminderTime,
                        'status' => 'pending',
                        'time_slot' => $time['label'],
                        'frequency' => 'daily'
                    ]);
                }
            }

            $currentDate->addDay();
        }
    }

    /**
     * Déterminer les heures de rappel selon la proximité de la deadline
     */
    private static function getReminderTimes($daysRemaining)
    {
        // Jour J ou jour précédent
        if ($daysRemaining <= 1) {
            return [
                ['hour' => 20, 'minute' => 30, 'label' => '20h30'],
                ['hour' => 22, 'minute' => 0, 'label' => '22h'],
            ];
        }

        // 2-3 jours avant
        if ($daysRemaining <= 3) {
            return [
                ['hour' => 20, 'minute' => 30, 'label' => '20h30'],
                ['hour' => 22, 'minute' => 0, 'label' => '22h'],
            ];
        }

        // 4-7 jours avant
        if ($daysRemaining <= 7) {
            return [
                ['hour' => 20, 'minute' => 30, 'label' => '20h30'],
                ['hour' => 22, 'minute' => 0, 'label' => '22h'],
            ];
        }

        // Plus de 7 jours : une seule notification
        return [
            ['hour' => 20, 'minute' => 30, 'label' => '20h30'],
        ];
    }

    /**
     * Obtenir les rappels en attente pour l'heure actuelle
     */
    public static function getPendingReminders()
    {
        $now = Carbon::now();
        $startOfHour = $now->copy()->startOfHour();
        $endOfHour = $now->copy()->endOfHour();

        return TaskReminder::where('status', 'pending')
            ->whereBetween('reminder_time', [$startOfHour, $endOfHour])
            ->with('task.course')
            ->get();
    }

    /**
     * Marquer un rappel comme envoyé
     */
    public static function markAsSent(TaskReminder $reminder)
    {
        $reminder->update(['status' => 'sent']);
    }
}
?>
