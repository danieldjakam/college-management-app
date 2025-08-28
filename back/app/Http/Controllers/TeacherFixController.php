<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherFixController extends Controller
{
    /**
     * Corriger les utilisateurs enseignants qui n'ont pas leur numéro de téléphone
     */
    public function fixTeacherContacts()
    {
        try {
            // Récupérer tous les utilisateurs avec le rôle teacher
            $teacherUsers = User::where('role', 'teacher')->get();
            
            $fixed = 0;
            $errors = [];
            
            foreach ($teacherUsers as $user) {
                // Chercher l'enseignant correspondant
                $teacher = Teacher::where('user_id', $user->id)->first();
                
                if ($teacher && $teacher->phone_number) {
                    // Mettre à jour le contact de l'utilisateur
                    $user->update(['contact' => $teacher->phone_number]);
                    
                    // Si l'email est du type "phone@school.local", le corriger aussi
                    if (str_ends_with($user->email, '@school.local') && 
                        is_numeric(str_replace('@school.local', '', $user->email))) {
                        
                        // Créer un email plus propre
                        $cleanName = strtolower(str_replace(' ', '.', $user->name));
                        $cleanEmail = $cleanName . '@cpb.cm';
                        
                        // Vérifier si cet email n'existe pas déjà
                        if (!User::where('email', $cleanEmail)->where('id', '!=', $user->id)->exists()) {
                            $user->update(['email' => $cleanEmail]);
                        }
                    }
                    
                    $fixed++;
                } else {
                    $errors[] = "Utilisateur {$user->name} (ID: {$user->id}) n'a pas d'enseignant correspondant";
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Correction terminée : {$fixed} utilisateurs corrigés",
                'fixed' => $fixed,
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la correction: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Corriger un utilisateur spécifique
     */
    public function fixSpecificTeacher(Request $request)
    {
        try {
            $userId = $request->user_id;
            $user = User::findOrFail($userId);
            
            if ($user->role !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet utilisateur n\'est pas un enseignant'
                ], 400);
            }
            
            $teacher = Teacher::where('user_id', $user->id)->first();
            
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun profil enseignant trouvé pour cet utilisateur'
                ], 404);
            }
            
            // Mettre à jour les informations
            $updates = [];
            
            if ($teacher->phone_number && $user->contact !== $teacher->phone_number) {
                $updates['contact'] = $teacher->phone_number;
            }
            
            if ($teacher->email && $user->email !== $teacher->email) {
                // Vérifier que l'email n'est pas déjà utilisé
                if (!User::where('email', $teacher->email)->where('id', '!=', $user->id)->exists()) {
                    $updates['email'] = $teacher->email;
                }
            }
            
            if (!empty($updates)) {
                $user->update($updates);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Utilisateur corrigé avec succès',
                    'updates' => $updates
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Aucune correction nécessaire'
                ]);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la correction: ' . $e->getMessage()
            ], 500);
        }
    }
}