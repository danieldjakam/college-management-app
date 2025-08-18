<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Teacher;
use App\Models\SchoolSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class DepartmentController extends Controller
{
    /**
     * Afficher la liste des départements
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Department::with(['headTeacher', 'teachers']);

            // Filtres optionnels
            if ($request->has('active_only') && $request->active_only) {
                $query->active();
            }

            $departments = $query->ordered()->get()->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'description' => $department->description,
                    'color' => $department->color,
                    'is_active' => $department->is_active,
                    'order' => $department->order,
                    'head_teacher' => $department->headTeacher ? [
                        'id' => $department->headTeacher->id,
                        'full_name' => $department->headTeacher->full_name,
                        'email' => $department->headTeacher->email
                    ] : null,
                    'stats' => $department->getStats(),
                    'created_at' => $department->created_at->format('d/m/Y')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $departments
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des départements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau département
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:departments,name',
                'code' => 'required|string|max:10|unique:departments,code',
                'description' => 'nullable|string',
                'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'head_teacher_id' => 'nullable|exists:teachers,id',
                'order' => 'nullable|integer|min:0'
            ]);

            $validatedData['color'] = $validatedData['color'] ?? '#6c757d';
            $validatedData['order'] = $validatedData['order'] ?? 0;

            $department = Department::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Département créé avec succès',
                'data' => $department->load('headTeacher')
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du département',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un département spécifique
     */
    public function show($id): JsonResponse
    {
        try {
            $department = Department::with(['headTeacher', 'teachers.user'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'department' => $department,
                    'stats' => $department->getStats(),
                    'teachers' => $department->getTeachersWithStats()
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Département introuvable'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du département',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un département
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $department = Department::findOrFail($id);

            $validatedData = $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('departments')->ignore($department->id)],
                'code' => ['required', 'string', 'max:10', Rule::unique('departments')->ignore($department->id)],
                'description' => 'nullable|string',
                'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'head_teacher_id' => 'nullable|exists:teachers,id',
                'is_active' => 'boolean',
                'order' => 'nullable|integer|min:0'
            ]);

            $department->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Département mis à jour avec succès',
                'data' => $department->load('headTeacher')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Département introuvable'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du département',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un département
     */
    public function destroy($id): JsonResponse
    {
        try {
            $department = Department::findOrFail($id);

            // Vérifier s'il y a des enseignants dans ce département
            if ($department->teachers()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce département car il contient des enseignants'
                ], 400);
            }

            $department->delete();

            return response()->json([
                'success' => true,
                'message' => 'Département supprimé avec succès'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Département introuvable'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du département',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assigner un enseignant à un département
     */
    public function assignTeacher(Request $request, $id): JsonResponse
    {
        try {
            $department = Department::findOrFail($id);

            $validatedData = $request->validate([
                'teacher_id' => 'required|exists:teachers,id'
            ]);

            $teacher = Teacher::findOrFail($validatedData['teacher_id']);
            $teacher->update(['department_id' => $department->id]);

            return response()->json([
                'success' => true,
                'message' => "Enseignant {$teacher->full_name} assigné au département {$department->name}",
                'data' => [
                    'teacher' => $teacher->load('department'),
                    'department' => $department->load('teachers')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retirer un enseignant d'un département
     */
    public function removeTeacher(Request $request, $id): JsonResponse
    {
        try {
            $department = Department::findOrFail($id);

            $validatedData = $request->validate([
                'teacher_id' => 'required|exists:teachers,id'
            ]);

            $teacher = Teacher::findOrFail($validatedData['teacher_id']);
            
            // Si l'enseignant était chef de département, le retirer
            if ($department->head_teacher_id === $teacher->id) {
                $department->update(['head_teacher_id' => null]);
            }

            $teacher->update(['department_id' => null]);

            return response()->json([
                'success' => true,
                'message' => "Enseignant {$teacher->full_name} retiré du département {$department->name}",
                'data' => [
                    'teacher' => $teacher->load('department'),
                    'department' => $department->load('teachers')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retrait',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Définir le chef de département
     */
    public function setHead(Request $request, $id): JsonResponse
    {
        try {
            $department = Department::findOrFail($id);

            $validatedData = $request->validate([
                'teacher_id' => 'required|exists:teachers,id'
            ]);

            $teacher = Teacher::findOrFail($validatedData['teacher_id']);

            if (!$department->canBeHead($teacher)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet enseignant ne peut pas être chef de ce département'
                ], 400);
            }

            $department->setHeadTeacher($teacher);

            return response()->json([
                'success' => true,
                'message' => "Enseignant {$teacher->full_name} nommé chef du département {$department->name}",
                'data' => $department->load('headTeacher')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la nomination',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter la liste des départements et leurs enseignants en PDF
     */
    public function exportPdf(): \Illuminate\Http\Response
    {
        try {
            // Récupérer tous les départements avec leurs enseignants
            $departments = Department::with(['teachers.user'])
                ->active()
                ->ordered()
                ->get();

            // Récupérer les paramètres de l'école
            $schoolSettings = SchoolSetting::getSettings();
            
            // Convertir le logo en base64 pour DOMPDF
            $logoBase64 = '';
            $schoolSettingsModel = SchoolSetting::first();
            if ($schoolSettingsModel && $schoolSettingsModel->school_logo) {
                $logoPath = storage_path('app/public/' . $schoolSettingsModel->school_logo);
                if (file_exists($logoPath)) {
                    $logoContent = file_get_contents($logoPath);
                    $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoContent);
                }
            }

            // Préparer les données pour le PDF
            $data = [
                'departments' => $departments,
                'schoolSettings' => $schoolSettings,
                'logoBase64' => $logoBase64,
                'currentDate' => now()->format('d/m/Y'),
                'academicYear' => now()->year . '/' . (now()->year + 1),
                'totalTeachers' => $departments->sum(function($dept) {
                    return $dept->teachers->count();
                })
            ];

            // Générer le HTML
            $html = $this->generateDepartmentListHtml($data);

            // Créer le PDF
            $pdf = Pdf::loadHtml($html);
            $pdf->setPaper('A4', 'portrait');
            
            $filename = 'Liste_Personnel_Enseignant_' . date('Y-m-d') . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML pour le PDF de la liste des départements
     */
    private function generateDepartmentListHtml($data): string
    {
        $departments = $data['departments'];
        $schoolSettings = $data['schoolSettings'];
        $logoBase64 = $data['logoBase64'];
        $currentDate = $data['currentDate'];
        $academicYear = $data['academicYear'];
        $totalTeachers = $data['totalTeachers'];

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Liste du Personnel Enseignant</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: Arial, sans-serif;
                    font-size: 10px;
                    line-height: 1.2;
                    color: #000;
                }
                
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 10px;
                    position: relative;
                }
                
                .header-content {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 20px;
                }
                
                .school-logo {
                    width: 60px;
                    height: 60px;
                    object-fit: contain;
                }
                
                .school-name {
                    font-size: 14px;
                    font-weight: bold;
                    margin-bottom: 5px;
                    text-transform: uppercase;
                }
                
                .school-details {
                    font-size: 9px;
                    margin-bottom: 3px;
                }
                
                .academic-year {
                    font-size: 11px;
                    font-weight: bold;
                    margin: 10px 0 5px 0;
                }
                
                .title {
                    font-size: 16px;
                    font-weight: bold;
                    text-transform: uppercase;
                    text-decoration: underline;
                    margin: 15px 0 20px 0;
                }
                
                .department-section {
                    margin-bottom: 25px;
                    page-break-inside: avoid;
                }
                
                .department-name {
                    background-color: #f0f0f0;
                    padding: 8px;
                    font-weight: bold;
                    font-size: 12px;
                    text-transform: uppercase;
                    border: 1px solid #000;
                    text-align: center;
                }
                
                .teachers-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 0;
                }
                
                .teachers-table th {
                    background-color: #e8e8e8;
                    border: 1px solid #000;
                    padding: 5px 2px;
                    text-align: center;
                    font-weight: bold;
                    font-size: 8px;
                    text-transform: uppercase;
                    line-height: 1.0;
                }
                
                .teachers-table td {
                    border: 1px solid #000;
                    padding: 3px 2px;
                    font-size: 9px;
                    vertical-align: middle;
                    line-height: 1.1;
                }
                
                .teachers-table .number-col {
                    width: 6%;
                    text-align: center;
                }
                
                .teachers-table .name-col {
                    width: 28%;
                    text-align: left;
                    padding-left: 4px;
                }
                
                .teachers-table .status-col {
                    width: 10%;
                    text-align: center;
                }
                
                .teachers-table .diploma-col {
                    width: 22%;
                    text-align: center;
                }
                
                .teachers-table .contact-col {
                    width: 24%;
                    text-align: center;
                }
                
                .teachers-table .date-col {
                    width: 10%;
                    text-align: center;
                }
                
                .page-number {
                    position: fixed;
                    bottom: 20px;
                    right: 50%;
                    font-size: 10px;
                }
                
                .empty-department {
                    text-align: center;
                    font-style: italic;
                    color: #666;
                    padding: 10px;
                    border: 1px solid #000;
                    border-top: none;
                }
                
                @page {
                    margin: 10mm 15mm;
                    size: A4;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="header-content">
                    ' . ($logoBase64 ? "<img src='{$logoBase64}' alt='Logo École' class='school-logo'>" : '') . '
                    <div>
                        <div class="school-name">' . strtoupper($schoolSettings['school_name'] ?? 'COLLEGE POLYVALENT BILINGUE DE DOUALA') . '</div>
                        <div class="school-details">BP : 4100 Tél : 233-43-25-47</div>
                        <div class="school-details">DOUALA</div>
                        <div class="school-details">Autorisation de création : N°185/MINESE/SG/DWESTVO/SDPES/SEPTC DU 16 JUIN 2015</div>
                        <div class="school-details">Autorisation d\'ouverture : N°210 MINESE/SG/DWESTVO/SDPES/SEPTC DU 06 NOVEMBRE 2015</div>
                    </div>
                </div>
                
                <div class="academic-year">Année scolaire ' . $academicYear . '</div>
                <div class="title">Liste du Personnel Enseignant CPBD</div>
            </div>';

        $globalCounter = 1;
        
        foreach ($departments as $department) {
            $html .= '
            <div class="department-section">
                <div class="department-name">' . strtoupper($department->name) . '</div>';
            
            if ($department->teachers->count() > 0) {
                $html .= '
                <table class="teachers-table">
                    <thead>
                        <tr>
                            <th class="number-col">N°</th>
                            <th class="name-col">NOMS ET PRÉNOMS</th>
                            <th class="status-col">STATUT</th>
                            <th class="diploma-col">DIPLÔME</th>
                            <th class="contact-col">CONTACTS</th>
                            <th class="date-col">DATE DE SERVICE</th>
                        </tr>
                    </thead>
                    <tbody>';
                
                foreach ($department->teachers as $teacher) {
                    $fullName = strtoupper($teacher->full_name);
                    $contact = $teacher->user?->contact ?? $teacher->phone_number ?? '';
                    $status = $this->getTeacherStatus($teacher);
                    $diploma = $teacher->qualification ?? '';
                    $serviceDate = $teacher->hire_date ? \Carbon\Carbon::parse($teacher->hire_date)->format('d/m/Y') : '';
                    
                    $html .= '
                        <tr>
                            <td class="number-col">' . $globalCounter . '.</td>
                            <td class="name-col">' . $fullName . '</td>
                            <td class="status-col">' . $status . '</td>
                            <td class="diploma-col">' . $diploma . '</td>
                            <td class="contact-col">' . $contact . '</td>
                            <td class="date-col">' . $serviceDate . '</td>
                        </tr>';
                    
                    $globalCounter++;
                }
                
                $html .= '
                    </tbody>
                </table>';
            } else {
                $html .= '<div class="empty-department">Aucun enseignant assigné à ce département</div>';
            }
            
            $html .= '</div>';
        }

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Déterminer le statut de l'enseignant
     */
    private function getTeacherStatus($teacher): string
    {
        // Utiliser directement le champ type_personnel s'il existe
        if (!empty($teacher->type_personnel)) {
            return $teacher->type_personnel;
        }
        
        // Logique de fallback pour les anciens enregistrements
        // Vérifier si c'est un chef de département
        if ($teacher->id === optional($teacher->department)->head_teacher_id) {
            return 'P'; // Principal/Chef
        }
        
        // Si l'enseignant a un compte utilisateur avec un rôle spécial
        if ($teacher->user && in_array($teacher->user->role, ['admin', 'surveillant_general'])) {
            return 'SP'; // Semi-Permanent (statut spécial)
        }
        
        // Si l'enseignant a des qualifications élevées
        $highQualifications = ['doctorat', 'phd', 'master', 'maitrise', 'licence'];
        $qualification = strtolower($teacher->qualification ?? '');
        foreach ($highQualifications as $qual) {
            if (strpos($qualification, $qual) !== false) {
                return 'P'; // Permanent
            }
        }
        
        // Par défaut, considérer comme vacataire
        return 'V';
    }
}
