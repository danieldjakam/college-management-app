<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TeacherImportController extends Controller
{
    /**
     * Importer des enseignants depuis un fichier CSV
     */
    public function importCsv(Request $request)
    {
        try {
            // Validation du fichier
            $request->validate([
                'file' => 'required|mimes:csv,txt|max:2048'
            ]);

            $file = $request->file('file');
            $path = $file->getRealPath();
            
            // Lire le fichier CSV
            $csvData = array_map('str_getcsv', file($path));
            $headers = array_shift($csvData); // Première ligne = en-têtes
            
            // Normaliser les en-têtes (enlever espaces, minuscules)
            $headers = array_map(function($header) {
                return strtolower(trim($header));
            }, $headers);
            
            $results = [
                'created' => 0,
                'updated' => 0,
                'errors' => []
            ];
            
            DB::beginTransaction();
            
            foreach ($csvData as $rowIndex => $row) {
                try {
                    // Créer un tableau associatif avec les en-têtes
                    $rowData = array_combine($headers, $row);
                    
                    if (!$rowData) {
                        $results['errors'][] = [
                            'line' => $rowIndex + 2,
                            'error' => 'Format de ligne invalide'
                        ];
                        continue;
                    }
                    
                    // Nettoyer les données
                    $rowData = $this->cleanData($rowData);
                    
                    // Valider les données
                    $validator = Validator::make($rowData, [
                        'nom' => 'required|string|max:255',
                        'prenom' => 'required|string|max:255',
                        'telephone' => 'nullable|string|max:20',
                        'email' => 'nullable|email|max:255',
                        'adresse' => 'nullable|string',
                        'date_naissance' => 'nullable|string',
                        'sexe' => 'nullable|in:m,f,M,F',
                        'qualification' => 'nullable|string|max:255',
                        'date_embauche' => 'nullable|string',
                        'type_personnel' => 'nullable|in:V,SP,P,v,sp,p',
                        'statut' => 'nullable'
                    ]);
                    
                    if ($validator->fails()) {
                        $results['errors'][] = [
                            'line' => $rowIndex + 2,
                            'errors' => $validator->errors()->toArray()
                        ];
                        continue;
                    }
                    
                    // Préparer les données pour la sauvegarde
                    $teacherData = [
                        'first_name' => $rowData['prenom'],
                        'last_name' => $rowData['nom'],
                        'phone_number' => $this->cleanPhoneNumber($rowData['telephone'] ?? ''),
                        'email' => $rowData['email'] ?? null,
                        'address' => $rowData['adresse'] ?? null,
                        'date_of_birth' => $this->parseDate($rowData['date_naissance'] ?? null),
                        'gender' => $this->parseGender($rowData['sexe'] ?? null),
                        'qualification' => $rowData['qualification'] ?? null,
                        'hire_date' => $this->parseDate($rowData['date_embauche'] ?? null) ?: now()->format('Y-m-d'),
                        'type_personnel' => $this->parseTypePersonnel($rowData['type_personnel'] ?? 'V'),
                        'is_active' => $this->parseStatus($rowData['statut'] ?? '1')
                    ];
                    
                    // Créer aussi un compte utilisateur si spécifié
                    $createUserAccount = isset($rowData['create_user_account']) && 
                                       $this->parseStatus($rowData['create_user_account']);
                    
                    // Vérifier si l'enseignant existe déjà
                    if (!empty($rowData['id'])) {
                        // Mise à jour par ID
                        $teacher = Teacher::find($rowData['id']);
                        if ($teacher) {
                            $teacher->update($teacherData);
                            $results['updated']++;
                        } else {
                            // ID fourni mais enseignant non trouvé, créer nouveau
                            Teacher::create($teacherData);
                            $results['created']++;
                        }
                    } else {
                        // Vérifier par nom/prénom/téléphone pour éviter les doublons
                        $existing = Teacher::where('first_name', $teacherData['first_name'])
                            ->where('last_name', $teacherData['last_name'])
                            ->where('phone_number', $teacherData['phone_number'])
                            ->first();
                        
                        if ($existing) {
                            $existing->update($teacherData);
                            $results['updated']++;
                        } else {
                            Teacher::create($teacherData);
                            $results['created']++;
                        }
                    }
                    
                } catch (\Exception $e) {
                    Log::error('Erreur import ligne ' . ($rowIndex + 2) . ': ' . $e->getMessage());
                    $results['errors'][] = [
                        'line' => $rowIndex + 2,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Import terminé : {$results['created']} créés, {$results['updated']} mis à jour",
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur import CSV: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Nettoyer les données du CSV
     */
    private function cleanData($rowData)
    {
        foreach ($rowData as $key => $value) {
            if (is_string($value)) {
                $cleanValue = trim($value);
                // Remplacer les valeurs "NON RENSEIGÉ" par null
                if (in_array(mb_strtoupper($cleanValue), [
                    'NON RENSEIGN�', 'NON RENSEIGNÉ', 'NON RENSEIGNE',
                    'NON RENSEIGNÉ', '', 'NULL', 'N/A', '-'
                ])) {
                    $rowData[$key] = null;
                } else {
                    $rowData[$key] = $cleanValue;
                }
            }
        }
        return $rowData;
    }
    
    /**
     * Nettoyer et formater le numéro de téléphone
     */
    private function cleanPhoneNumber($phone)
    {
        if (empty($phone)) {
            return '000000000';
        }
        
        // Nettoyer le numéro
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Si trop court, compléter avec des zéros
        if (strlen($phone) < 9) {
            $phone = str_pad($phone, 9, '0', STR_PAD_RIGHT);
        }
        
        return $phone;
    }
    
    /**
     * Parser une date depuis différents formats
     */
    private function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }
        
        try {
            // Essayer différents formats courants
            $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];
            
            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $dateString);
                    if ($date !== false) {
                        return $date->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // Essayer le parsing automatique
            return Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Parser le genre
     */
    private function parseGender($gender)
    {
        if (empty($gender)) {
            return null;
        }
        
        $genderLower = strtolower(trim($gender));
        
        if (in_array($genderLower, ['m', 'masculin', 'male', 'homme'])) {
            return 'm';
        }
        
        if (in_array($genderLower, ['f', 'feminin', 'féminin', 'female', 'femme'])) {
            return 'f';
        }
        
        return null;
    }
    
    /**
     * Parser le statut actif/inactif
     */
    private function parseStatus($status)
    {
        if (empty($status)) {
            return true; // Actif par défaut
        }
        
        if (is_string($status)) {
            $statusLower = strtolower(trim($status));
            if (in_array($statusLower, ['0', 'false', 'inactif', 'inactive', 'non'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Parser le type de personnel
     */
    private function parseTypePersonnel($type)
    {
        if (empty($type)) {
            return 'V'; // Vacataire par défaut
        }
        
        $typeUpper = strtoupper(trim($type));
        
        if (in_array($typeUpper, ['V', 'VACATAIRE', 'VAC'])) {
            return 'V';
        }
        
        if (in_array($typeUpper, ['SP', 'SEMI-PERMANENT', 'SEMI_PERMANENT', 'SEMIPERMANENT'])) {
            return 'SP';
        }
        
        if (in_array($typeUpper, ['P', 'PERMANENT', 'PERM'])) {
            return 'P';
        }
        
        return 'V'; // Par défaut
    }
    
    /**
     * Télécharger un template CSV
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_enseignants.csv"'
        ];
        
        $columns = [
            'id',
            'nom',
            'prenom',
            'telephone',
            'email',
            'adresse',
            'date_naissance',
            'sexe',
            'qualification',
            'date_embauche',
            'type_personnel',
            'statut'
        ];
        
        $csvContent = implode(',', $columns) . "\n";
        $csvContent .= ",DUPONT,Jean,696118389,jean.dupont@cpb.cm,Douala,15/05/1985,M,Licence Mathématiques,01/09/2020,V,1\n";
        $csvContent .= ",MARTIN,Sophie,696118390,sophie.martin@cpb.cm,Yaoundé,10/12/1983,F,Master Physique,15/08/2019,P,1\n";
        $csvContent .= ",KAMENI,Paul,696118391,paul.kameni@cpb.cm,Bafoussam,22/03/1986,M,BTS Comptabilité,10/01/2021,SP,1\n";
        
        return response($csvContent, 200, $headers);
    }
}