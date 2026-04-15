<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Resource;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class ResourceController extends Controller
{
    /**
     * Liste des ressources d'un cours
     */
    public function index($courseId)
    {
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

        return response()->json($course->resources);
    }

    /**
     * Upload d'une ressource pour un cours
     */
    public function store(Request $request, $courseId)
    {
        $course = Course::with('user:id,filiere,annee')->findOrFail($courseId);
        $user = Auth::user();

        // Seul le propriétaire peut uploader des ressources
        if ($course->user_id !== $user->id) {
            return response()->json(['error' => 'Seul le propriétaire du cours peut ajouter des ressources'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'file'  => 'required|file|mimes:pdf,doc,docx,ppt,pptx,txt,jpg,png|max:2048',
        ]);

        // Sauvegarde du fichier dans storage/app/public/resources avec le nom original
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileName = pathinfo($originalName, PATHINFO_FILENAME) . '_' . time() . '.' . $extension;
        $path = $file->storeAs('resources', $fileName, 'public');

        $resource = $course->resources()->create([
            'title'       => $request->title,
            'file_url'    => $path,
            'uploaded_by' => Auth::id(),
        ]);

        return response()->json($resource, 201);
    }

    /**
     * Supprimer une ressource
     */
    public function destroy($id)
    {
        $resource = Resource::with('course.user:id,filiere,annee')->findOrFail($id);
        $user = Auth::user();

        // Seul le propriétaire peut supprimer des ressources
        if ($resource->course->user_id !== $user->id) {
            return response()->json(['error' => 'Seul le propriétaire du cours peut supprimer cette ressource'], 403);
        }

        // Supprimer le fichier du storage
        if ($resource->file_url && Storage::disk('public')->exists($resource->file_url)) {
            Storage::disk('public')->delete($resource->file_url);
        }

        $resource->delete();

        return response()->json(['message' => 'Ressource supprimée avec succès']);
    }

    /**
     * Télécharger ou voir une ressource
     */
    public function download($id)
    {
        $resource = Resource::with('course.user:id,filiere,annee')->findOrFail($id);
        $user = Auth::user();

        // Vérifier l'accès : propriétaire OU même filière
        if ($resource->course->user_id !== $user->id) {
            if (!$user->filiere || $resource->course->user->filiere !== $user->filiere) {
                return response()->json(['error' => 'Accès interdit'], 403);
            }
        }

        if (!Storage::disk('public')->exists($resource->file_url)) {
            return response()->json(['error' => 'Fichier introuvable'], 404);
        }

        $filePath = Storage::disk('public')->path($resource->file_url);
        $originalName = basename($resource->file_url);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        
        // Utiliser le titre avec l'extension du fichier original
        $fileName = $resource->title . '.' . $extension;
        
        // Obtenir le type MIME du fichier
        $mimeType = File::mimeType($filePath);
        
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
        ]);
    }
}
