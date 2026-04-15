<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TaskReminder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TaskReminderController extends Controller
{
    /**
     * Obtenir les rappels en attente pour l'utilisateur actuel
     */
    public function pending(Request $request)
    {
        $user = Auth::user();

        // Récupérer les rappels en attente pour les tâches de l'utilisateur
        $now = Carbon::now();
        $startOfHour = $now->copy()->startOfHour();
        $endOfHour = $now->copy()->endOfHour();

        $reminders = TaskReminder::where('status', 'pending')
            ->whereBetween('reminder_time', [$startOfHour, $endOfHour])
            ->with(['task.course' => function ($query) use ($user) {
                // Ne retourner que les tâches des cours de l'utilisateur
                $query->where('user_id', $user->id);
            }])
            ->get()
            ->filter(function ($reminder) {
                // Filtrer pour ne garder que les reminders avec un cours
                return $reminder->task && $reminder->task->course;
            })
            ->values();

        return response()->json($reminders);
    }

    /**
     * Marquer un rappel comme envoyé
     */
    public function markAsSent($id)
    {
        $reminder = TaskReminder::findOrFail($id);
        
        // Vérifier que l'utilisateur a accès à cette tâche
        $user = Auth::user();
        if ($reminder->task->course->user_id !== $user->id) {
            return response()->json(['error' => 'Accès interdit'], 403);
        }

        $reminder->update(['status' => 'sent']);

        return response()->json($reminder);
    }

    /**
     * Synchroniser les rappels (endpoint pour le polling côté client)
     */
    public function sync(Request $request)
    {
        $reminders = $this->pending($request);
        
        return response()->json([
            'status' => 'ok',
            'reminders' => $reminders->getData(true),
            'count' => count($reminders->getData(true))
        ]);
    }

    /**
     * Obtenir tous les rappels d'un utilisateur
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $reminders = TaskReminder::whereHas('task.course', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['task.course'])
        ->orderBy('reminder_time', 'desc')
        ->paginate(20);

        return response()->json($reminders);
    }

    /**
     * Obtenir les stats des rappels
     */
    public function stats(Request $request)
    {
        $user = Auth::user();

        $stats = TaskReminder::whereHas('task.course', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->selectRaw('status, COUNT(*) as count')
        ->groupBy('status')
        ->get();

        return response()->json([
            'pending' => $stats->where('status', 'pending')->first()?->count ?? 0,
            'sent' => $stats->where('status', 'sent')->first()?->count ?? 0,
            'cancelled' => $stats->where('status', 'cancelled')->first()?->count ?? 0,
        ]);
    }
}
?>
