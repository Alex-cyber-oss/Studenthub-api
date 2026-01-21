<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'filiere'  => 'nullable|string|max:255',
            'annee'    => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'filiere'  => $request->filiere,
            'annee'    => $request->annee,
        ]);

        $token = $user->createToken('studenthub')->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie',
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Connexion utilisateur
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        $token = $user->createToken('studenthub')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'user'  => $user,
            'token' => $token,
        ], 200);
    }

    /**
     * Déconnexion utilisateur
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Déconnecté avec succès'
        ]);
    }

    /**
     * Récupérer l'utilisateur connecté
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Mettre à jour le profil de l'utilisateur
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'    => 'nullable|string|max:255',
            'filiere' => 'nullable|string|max:255',
            'annee'   => 'nullable|string|max:255',
            'password'=> 'nullable|string|min:6',
        ]);

        $data = $request->only(['name', 'filiere', 'annee']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json($user);
    }
}
