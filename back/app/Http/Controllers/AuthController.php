<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct()
    {
        // Middleware JWT sera appliqué seulement aux routes protégées
    }

    public function login(Request $request)
    {
        // PATCH TEMPORAIRE CORS
        $origin = $request->header('Origin');
        $allowedOrigins = [
            'http://admin.cpb-douala.com',
            'https://admin.cpb-douala.com'
        ];
        
        if (in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, X-Token-Auth, Authorization, Accept');
            header('Access-Control-Allow-Credentials: true');
        }
        
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            $response = response()->json($validator->errors(), 422);
            if (in_array($origin, $allowedOrigins)) {
                $response->header('Access-Control-Allow-Origin', $origin);
            }
            return $response;
        }

        $credentials = $request->only('username', 'password');

        if (!$token = auth()->attempt($credentials)) {
            $response = response()->json([
                'success' => false,
                'message' => 'Identifiants invalides. Vérifiez votre nom d\'utilisateur et mot de passe.',
                'error' => 'Unauthorized'
            ], 401);
            
            if (in_array($origin, $allowedOrigins)) {
                $response->header('Access-Control-Allow-Origin', $origin);
            }
            return $response;
        }

        $response = $this->respondWithToken($token);
        if (in_array($origin, $allowedOrigins)) {
            $response->header('Access-Control-Allow-Origin', $origin);
        }
        return $response;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'username' => 'required|string|between:3,100|unique:users',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'role' => 'required|string|in:admin,principal,teacher,accountant,user,surveillant_general,general_accountant,comptable_superieur',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    public function me()
    {
        $user = auth()->user();
        
        // Si l'utilisateur est un enseignant, ajouter son teacher_id
        if ($user->role === 'teacher') {
            $teacher = \App\Models\Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $userData = $user->toArray();
                $userData['teacher_id'] = $teacher->id;
                $userData['teacher'] = $teacher;
                return response()->json($userData);
            }
        }
        
        return response()->json($user);
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
                'new_password_confirmation' => 'required|string'
            ], [
                'current_password.required' => 'Le mot de passe actuel est requis',
                'new_password.required' => 'Le nouveau mot de passe est requis',
                'new_password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères',
                'new_password.confirmed' => 'La confirmation du mot de passe ne correspond pas',
                'new_password_confirmation.required' => 'La confirmation du mot de passe est requise'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier le mot de passe actuel
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le mot de passe actuel est incorrect'
                ], 422);
            }

            // Vérifier que le nouveau mot de passe est différent de l'actuel
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le nouveau mot de passe doit être différent du mot de passe actuel'
                ], 422);
            }

            // Mettre à jour le mot de passe
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de mot de passe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function respondWithToken($token)
    {
        $user = auth()->user();
        $userData = $user->toArray();
        
        // Si l'utilisateur est un enseignant, ajouter son teacher_id
        if ($user->role === 'teacher') {
            $teacher = \App\Models\Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $userData['teacher_id'] = $teacher->id;
                $userData['teacher'] = $teacher;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => $userData
        ]);
    }
}