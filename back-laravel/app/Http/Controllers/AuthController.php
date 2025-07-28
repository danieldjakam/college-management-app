<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Login user (admin/comptable) or teacher
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->only('username', 'password');

            if (!$credentials['username'] || !$credentials['password']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remplir tous les champs du formulaire !!'
                ], 401);
            }

            // Try to authenticate as user (admin/comptable)
            $user = User::where('username', $credentials['username'])
                       ->orWhere('email', $credentials['username'])
                       ->first();

            if ($user && Hash::check($credentials['password'], $user->password)) {
                // Create a simple token for compatibility with frontend
                $token = 'Bearer ' . base64_encode($user->username . ':' . time());
                
                return response()->json([
                    'success' => true,
                    'token' => $token,
                    'status' => $user->role
                ]);
            }

            // Try to authenticate as teacher
            $teacher = Teacher::where('matricule', $credentials['username'])->first();

            if ($teacher && $teacher->password === $credentials['password']) {
                // Create a simple token for compatibility with frontend
                $token = 'Bearer ' . base64_encode($teacher->matricule . ':' . time());
                
                return response()->json([
                    'success' => true,
                    'token' => $token,
                    'status' => 'en',
                    'classId' => $teacher->class_id,
                    'school_id' => $teacher->school_id
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non reconnu'
            ], 401);

        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la connexion à la base de données.'
            ], 500);
        }
    }

    /**
     * Register new admin/comptable user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:5|max:30|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'confirm' => 'required|same:password',
            'role' => 'required|in:ad,comp'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 401);
        }

        $user = User::create([
            'id' => $this->generateUserId($request->username),
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'school_id' => auth()->user()->school_id ?? 'GSBPL_001',
            'role' => $request->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès'
        ], 201);
    }

    /**
     * Get current user info
     */
    public function getInfos()
    {
        $user = auth()->user();
        
        if ($user instanceof Teacher) {
            return response()->json($user->load('schoolClass'));
        }

        return response()->json($user);
    }

    /**
     * Get teacher or admin info
     */
    public function getTeacherOrAdmin()
    {
        $user = auth()->user();
        
        if ($user instanceof Teacher) {
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'subname' => $user->subname,
                'matricule' => $user->matricule,
                'class_id' => $user->class_id,
                'type' => 'teacher'
            ]);
        }

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'type' => 'user'
        ]);
    }

    /**
     * Get all admin users
     */
    public function getAllAdmin()
    {
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Confirm admin password
     */
    public function confirmAdminPassword(Request $request)
    {
        $user = auth()->user();
        
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe confirmé'
        ]);
    }

    /**
     * Update user or admin
     */
    public function updateUserOrAdmin(Request $request)
    {
        $user = auth()->user();
        
        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|string|min:5|max:30|unique:users,username,' . $user->id,
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 401);
        }

        $updateData = $request->only(['username', 'email']);
        
        if ($request->password) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès'
        ]);
    }

    /**
     * Delete admin user
     */
    public function deleteAdmin($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }

    /**
     * Generate unique user ID
     */
    private function generateUserId($username)
    {
        return 'USER_' . strtoupper(substr($username, 0, 8)) . '_' . time();
    }

    /**
     * Logout user
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'message' => 'Déconnecté avec succès'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion'
            ], 500);
        }
    }
}