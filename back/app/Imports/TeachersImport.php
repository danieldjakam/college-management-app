<?php

namespace App\Imports;

use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TeachersImport implements ToCollection, WithHeadingRow
{
    protected $results = [
        'created' => 0,
        'updated' => 0,
        'errors' => []
    ];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                // Convertir les données pour la validation
                $rowData = $row->toArray();
                
                // Convertir le téléphone en string si c'est un nombre
                if (isset($rowData['telephone'])) {
                    if (is_numeric($rowData['telephone'])) {
                        $rowData['telephone'] = (string) $rowData['telephone'];
                    } elseif (empty($rowData['telephone'])) {
                        $rowData['telephone'] = '';
                    }
                }
                
                // Convertir l'ID en string s'il existe
                if (isset($rowData['id']) && !empty($rowData['id'])) {
                    $rowData['id'] = (string) $rowData['id'];
                }

                // Nettoyer les valeurs "NON RENSEIGÉ"
                $rowData = $this->cleanNonRenseigne($rowData);
                
                $validator = Validator::make($rowData, [
                    'id' => 'nullable|string',
                    'nom' => 'required|string|max:255',
                    'prenom' => 'required|string|max:255', 
                    'telephone' => 'nullable|string|max:20',
                    'email' => 'nullable|email|max:255',
                    'adresse' => 'nullable|string',
                    'date_naissance' => 'nullable|string',
                    'sexe' => 'nullable|in:m,f,M,F,masculin,feminin,Masculin,Feminin,MASCULIN,FEMININ',
                    'qualification' => 'nullable|string|max:255',
                    'date_embauche' => 'nullable|string',
                    'statut' => 'nullable|in:0,1,actif,inactif,Actif,Inactif,ACTIF,INACTIF'
                ]);

                if ($validator->fails()) {
                    $this->results['errors'][] = [
                        'line' => $index + 2,
                        'errors' => $validator->errors()->toArray()
                    ];
                    continue;
                }

                $data = [
                    'first_name' => trim($rowData['prenom']),
                    'last_name' => trim($rowData['nom']),
                    'phone_number' => $this->cleanPhoneNumber($rowData['telephone']),
                    'email' => $rowData['email'] ? trim($rowData['email']) : null,
                    'address' => $rowData['adresse'] ? trim($rowData['adresse']) : null,
                    'date_of_birth' => $this->parseDate($rowData['date_naissance']),
                    'gender' => $this->parseGender($rowData['sexe']),
                    'qualification' => $rowData['qualification'] ? trim($rowData['qualification']) : null,
                    'hire_date' => $this->parseDate($rowData['date_embauche']),
                    'type_personnel' => 'V', // Défaut: Vacataire
                    'is_active' => $this->parseStatus($rowData['statut'] ?? 1)
                ];

                $existingTeacher = null;
                
                // Si un ID est fourni, chercher par ID
                if (!empty($rowData['id'])) {
                    $existingTeacher = Teacher::find($rowData['id']);
                    if ($existingTeacher) {
                        $existingTeacher->update($data);
                        $this->results['updated']++;
                    } else {
                        $this->results['errors'][] = [
                            'line' => $index + 2,
                            'errors' => ['ID ' . $rowData['id'] . ' non trouvé']
                        ];
                        continue;
                    }
                } else {
                    // Pas d'ID fourni, créer un nouvel enseignant
                    // Vérifier s'il existe déjà par nom/prénom/téléphone pour éviter les doublons
                    $existing = Teacher::where('first_name', $data['first_name'])
                        ->where('last_name', $data['last_name'])
                        ->where('phone_number', $data['phone_number'])
                        ->first();
                    
                    if ($existing) {
                        // Mettre à jour l'enseignant existant
                        $existing->update($data);
                        $this->results['updated']++;
                    } else {
                        // Créer un nouvel enseignant
                        Teacher::create($data);
                        $this->results['created']++;
                    }
                }

            } catch (\Exception $e) {
                $this->results['errors'][] = [
                    'line' => $index + 2,
                    'errors' => ['Erreur lors du traitement: ' . $e->getMessage()]
                ];
            }
        }
    }

    private function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            // Essayer différents formats de date
            $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];
            
            foreach ($formats as $format) {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            }
            
            // Si aucun format ne marche, essayer le parsing automatique
            return Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseGender($gender)
    {
        if (empty($gender)) {
            return null;
        }

        $genderLower = strtolower(trim($gender));
        
        if (in_array($genderLower, ['m', 'masculin', 'male'])) {
            return 'm';
        }
        
        if (in_array($genderLower, ['f', 'feminin', 'féminin', 'female'])) {
            return 'f';
        }
        
        return null;
    }

    private function parseStatus($status)
    {
        if (is_string($status)) {
            $statusLower = strtolower($status);
            if ($statusLower === 'actif') return true;
            if ($statusLower === 'inactif') return false;
        }
        
        return (bool) $status;
    }

    private function cleanNonRenseigne($rowData)
    {
        foreach ($rowData as $key => $value) {
            if (is_string($value)) {
                $cleanValue = trim($value);
                if (in_array(strtoupper($cleanValue), [
                    'NON RENSEIGN�', 'NON RENSEIGNÉ', 'NON RENSEIGNE', 
                    'NON RENSEIGNÉ', '', 'NULL', 'N/A'
                ])) {
                    $rowData[$key] = null;
                }
            }
        }
        return $rowData;
    }

    private function cleanPhoneNumber($phone)
    {
        if (empty($phone)) {
            return '000000000'; // Numéro par défaut si vide
        }
        
        // Nettoyer le numéro de téléphone
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Si trop court, compléter avec des zéros
        if (strlen($phone) < 9) {
            $phone = str_pad($phone, 9, '0', STR_PAD_RIGHT);
        }
        
        // Si pas de préfixe pays, ajouter +237 pour le Cameroun
        if (!str_starts_with($phone, '+')) {
            $phone = '+237' . $phone;
        }
        
        return $phone;
    }

    public function getResults()
    {
        return $this->results;
    }
}