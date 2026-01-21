<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TaskController extends Controller
{
    /**
     * Liste des tâches d'un cours
     */
    public function index(Request $request)
    {
        $courseId = $request->query('course_id');

        if (!$courseId) {
            return response()->json(['error' => 'Paramètre course_id requis'], 400);
        }

        $course = Course::with('user:id,filiere,annee')->findOrFail($courseId);
        $user = Auth::user();

        // Vérifier l'accès : propriétaire OU même filière et année
        if ($course->user_id !== $user->id) {
            if (!$user->filiere || $course->user->filiere !== $user->filiere) {
                return response()->json(['error' => 'Accès interdit'], 403);
            }
            // Vérifier aussi l'année si elle est définie
            if ($user->annee && $course->user->annee && $course->user->annee !== $user->annee) {
                return response()->json(['error' => 'Accès interdit'], 403);
            }
        }

        return response()->json($course->tasks);
    }

    /**
     * Créer une nouvelle tâche pour un cours
     */
    public function store(Request $request)
    {
        $courseId = $request->input('course_id');

        if (!$courseId) {
            return response()->json(['error' => 'Paramètre course_id requis'], 400);
        }

        $course = Course::with('user:id,filiere,annee')->findOrFail($courseId);
        $user = Auth::user();

        // Seul le propriétaire peut créer/modifier des tâches
        if ($course->user_id !== $user->id) {
            return response()->json(['error' => 'Seul le propriétaire du cours peut ajouter des tâches'], 403);
        }

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline'    => 'required|date_format:Y-m-d',
            'status'      => 'nullable|string|in:a_faire,termine',
        ]);

        // Convertir la date au format datetime pour la base de données
        $deadline = $request->input('deadline') . ' 00:00:00';

        $task = $course->tasks()->create([
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
            'deadline'    => $deadline,
            'status'      => $request->input('status') ?? 'a_faire',
        ]);

        return response()->json($task, 201);
    }

    /**
     * Mettre à jour une tâche
     */
    public function update(Request $request, $id)
    {
        $task = Task::with('course.user:id,filiere,annee')->findOrFail($id);
        $user = Auth::user();

        // Seul le propriétaire peut modifier/supprimer des tâches
        if ($task->course->user_id !== $user->id) {
            return response()->json(['error' => 'Seul le propriétaire du cours peut modifier cette tâche'], 403);
        }

        $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'deadline'    => 'nullable|date_format:Y-m-d',
            'status'      => 'nullable|string|in:a_faire,termine',
        ]);

        $updateData = $request->only(['title', 'description', 'status']);
        
        // Si une deadline est fournie, la convertir au format datetime
        if ($request->has('deadline')) {
            $updateData['deadline'] = $request->input('deadline') . ' 00:00:00';
        }

        $task->update($updateData);

        return response()->json($task);
    }

    /**
     * Afficher une tâche
     */
    public function show($id)
    {
        $task = Task::with('course.user:id,filiere,annee')->findOrFail($id);
        $user = Auth::user();

        // Vérifier l'accès : propriétaire OU même filière
        if ($task->course->user_id !== $user->id) {
            if (!$user->filiere || $task->course->user->filiere !== $user->filiere) {
                return response()->json(['error' => 'Accès interdit'], 403);
            }
        }

        return response()->json($task);
    }

    /**
     * Supprimer une tâche
     */
    public function destroy($id)
    {
        $task = Task::with('course.user:id,filiere,annee')->findOrFail($id);
        $user = Auth::user();

        // Seul le propriétaire peut supprimer des tâches
        if ($task->course->user_id !== $user->id) {
            return response()->json(['error' => 'Seul le propriétaire du cours peut supprimer cette tâche'], 403);
        }

        $task->delete();

        return response()->json(['message' => 'Tâche supprimée avec succès']);
    }

    /**
     * Marquer une tâche comme terminée pour l'utilisateur actuel
     */
    public function toggleComplete($id)
    {
        $task = Task::with('course.user:id,filiere,annee')->findOrFail($id);
        $user = Auth::user();

        // Vérifier l'accès : propriétaire OU même filière
        if ($task->course->user_id !== $user->id) {
            if (!$user->filiere || $task->course->user->filiere !== $user->filiere) {
                return response()->json(['error' => 'Accès interdit'], 403);
            }
        }

        // Basculer le statut
        $newStatus = $task->status === 'a_faire' ? 'termine' : 'a_faire';
        $task->update(['status' => $newStatus]);

        return response()->json($task);
    }

    /**
     * Récupérer les tâches à venir (prochains jours)
     */
    public function upcoming(Request $request)
    {
        $days = $request->query('days', 7);
        $user = Auth::user();

        $upcomingTasks = Task::whereHas('course', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->where('status', 'a_faire')
        ->where('deadline', '<=', now()->addDays($days))
        ->where('deadline', '>=', now())
        ->with('course:id,title')
        ->orderBy('deadline', 'asc')
        ->get();

        return response()->json($upcomingTasks);
    }

    /**
     * Récupérer les tâches pour le calendrier
     */
    public function calendar(Request $request)
    {
        $month = $request->query('month');
        $year = $request->query('year');
        $user = Auth::user();

        if (!$month || !$year) {
            return response()->json(['error' => 'Paramètres month et year requis'], 400);
        }

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $tasks = Task::whereHas('course', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->whereBetween('deadline', [$startDate, $endDate])
        ->with('course:id,title')
        ->orderBy('deadline', 'asc')
        ->get();

        return response()->json($tasks);
    }
}