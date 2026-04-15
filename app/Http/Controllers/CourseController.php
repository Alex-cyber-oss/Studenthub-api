<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    /**
     * Liste des cours de l'utilisateur connecté
     */
    public function index()
    {
        $courses = Auth::user()->courses()->with('tasks', 'resources')->get();
        return response()->json($courses);
    }

    /**
     * Liste des cours partagés par filière et année
     */
    public function shared($filiere, $annee = null)
    {
        $user = Auth::user();
        
        // Récupérer les cours des autres utilisateurs de la même filière et année
        $sharedCourses = Course::whereHas('user', function($query) use ($filiere, $annee, $user) {
            $query->where('filiere', $filiere)
                  ->where('id', '!=', $user->id);
            if ($annee) {
                $query->where('annee', $annee);
            }
        })
        ->with('tasks', 'resources', 'user:id,name,filiere,annee')
        ->get();

        return response()->json($sharedCourses);
    }

    /**
     * Créer un nouveau cours
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:255',
        ]);

        $course = Auth::user()->courses()->create([
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
            'category'    => $request->input('category'),
        ]);

        return response()->json($course, 201);
    }

    /**
     * Afficher un cours précis
     */
    public function show($id)
    {
        $course = Course::with('tasks', 'resources', 'user:id,name,filiere')->findOrFail($id);
        $user = Auth::user();

        // Vérifier que le cours appartient à l'utilisateur connecté OU qu'ils sont de la même filière et année
        if ($course->user_id !== $user->id) {
            // Vérifier si l'utilisateur et le créateur du cours sont de la même filière et année
            if (!$user->filiere || $course->user->filiere !== $user->filiere) {
                return response()->json(['error' => 'Accès interdit'], 403);
            }
            // Vérifier aussi l'année si elle est définie
            if ($user->annee && $course->user->annee && $course->user->annee !== $user->annee) {
                return response()->json(['error' => 'Accès interdit'], 403);
            }
        }

        return response()->json($course);
    }

    /**
     * Mettre à jour un cours
     */
    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        if ($course->user_id !== Auth::id()) {
            return response()->json(['error' => 'Accès interdit'], 403);
        }

        $course->update($request->only(['title', 'description', 'category']));

        return response()->json($course);
    }

    /**
     * Supprimer un cours
     */
    public function destroy($id)
    {
        $course = Course::findOrFail($id);

        if ($course->user_id !== Auth::id()) {
            return response()->json(['error' => 'Accès interdit'], 403);
        }

        Course::destroy($id);

        return response()->json(['message' => 'Cours supprimé avec succès']);
    }

    /**
     * Statistiques du tableau de bord
     */
    public function stats()
    {
        $user = Auth::user();
        $courses = $user->courses()->with('tasks')->get();
        
        $totalCourses = $courses->count();
        $totalTasks = $courses->sum(function($course) {
            return $course->tasks->count();
        });
        $completedTasks = $courses->sum(function($course) {
            return $course->tasks->where('status', 'termine')->count();
        });
        $pendingTasks = $courses->sum(function($course) {
            return $course->tasks->where('status', 'a_faire')->count();
        });
        
        // Tâches proches de la deadline (7 prochains jours)
        $upcomingDeadlines = \App\Models\Task::whereHas('course', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->where('status', 'a_faire')
        ->where('deadline', '<=', now()->addDays(7))
        ->where('deadline', '>=', now())
        ->count();

        // Compter les cours par catégorie
        $coursesByCategory = $courses->groupBy('category')->map->count();

        return response()->json([
            'total_courses' => $totalCourses,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'pending_tasks' => $pendingTasks,
            'upcoming_deadlines' => $upcomingDeadlines,
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0,
            'courses_by_category' => $coursesByCategory,
        ]);
    }

    /**
     * Dupliquer un cours partagé vers les cours de l'utilisateur
     */
    public function duplicate($id)
    {
        $sourceCourse = Course::with('tasks', 'resources')->findOrFail($id);
        $user = Auth::user();

        // Vérifier que c'est pas déjà son cours
        if ($sourceCourse->user_id === $user->id) {
            return response()->json(['error' => 'Vous êtes déjà propriétaire de ce cours'], 400);
        }

        // Vérifier l'accès : même filière et année
        if (!$user->filiere || $sourceCourse->user->filiere !== $user->filiere) {
            return response()->json(['error' => 'Accès interdit'], 403);
        }
        if ($user->annee && $sourceCourse->user->annee && $sourceCourse->user->annee !== $user->annee) {
            return response()->json(['error' => 'Accès interdit'], 403);
        }

        // Vérifier si l'utilisateur a déjà une copie de ce cours
        $existingCopy = $user->courses()
            ->where('title', 'like', $sourceCourse->title . '%')
            ->first();
        
        if ($existingCopy) {
            return response()->json(['error' => 'Vous avez déjà ajouté ce cours'], 400);
        }

        // Créer une copie du cours
        $newCourse = $user->courses()->create([
            'title' => $sourceCourse->title . ' (copie)',
            'description' => $sourceCourse->description,
            'category' => $sourceCourse->category,
        ]);

        // Copier les tâches
        foreach ($sourceCourse->tasks as $task) {
            $newCourse->tasks()->create([
                'title' => $task->title,
                'description' => $task->description,
                'deadline' => $task->deadline,
                'status' => 'a_faire', // Réinitialiser le statut à "À faire"
            ]);
        }

        // Copier les ressources
        foreach ($sourceCourse->resources as $resource) {
            $newCourse->resources()->create([
                'title' => $resource->title,
                'file_url' => $resource->file_url,
            ]);
        }

        return response()->json($newCourse, 201);
    }
}
