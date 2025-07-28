<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SchoolYear;
use App\Models\ClassSeries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Obtenir tous les élèves d'une série de classe
     */
    public function getByClassSeries($seriesId)
    {
        try {
            // Récupérer l'année scolaire courante
            $currentYear = SchoolYear::where('is_current', true)->first();
            
            if (!$currentYear) {
                // Si aucune année courante, prendre la première année active
                $currentYear = SchoolYear::where('is_active', true)->first();
            }
            
            if (!$currentYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            // Récupérer les élèves
            $studentsQuery = Student::with(['schoolYear', 'classSeries'])
                ->where('class_series_id', $seriesId)
                ->where('is_active', true);
                
            // Filtrer par année scolaire si elle existe
            if ($currentYear) {
                $studentsQuery->where('school_year_id', $currentYear->id);
            }
            
            $students = $studentsQuery
                ->orderBy('order', 'asc')
                ->orderByRaw('COALESCE(last_name, name) ASC')
                ->orderByRaw('COALESCE(first_name, subname) ASC')
                ->get();

            // Récupérer les informations de la série
            $series = ClassSeries::with(['schoolClass.level.section'])->find($seriesId);
            
            if (!$series) {
                return response()->json([
                    'success' => false,
                    'message' => 'Série de classe non trouvée'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $students,
                    'series' => $series,
                    'school_year' => $currentYear,
                    'total' => $students->count()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getByClassSeries: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des élèves',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouvel élève
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'place_of_birth' => 'required|string|max:255',
            'gender' => 'required|in:M,F',
            'parent_name' => 'required|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'parent_email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'class_series_id' => 'required|exists:class_series,id',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Générer le numéro d'élève
            $schoolYear = SchoolYear::find($request->school_year_id);
            $studentNumber = Student::generateStudentNumber(
                $schoolYear->start_date, 
                $request->class_series_id
            );

            // Calculer l'ordre automatiquement (position alphabétique)
            $maxOrder = Student::where('class_series_id', $request->class_series_id)
                ->where('school_year_id', $request->school_year_id)
                ->max('order') ?: 0;

            $studentData = $request->all();
            $studentData['student_number'] = $studentNumber;
            $studentData['order'] = $maxOrder + 1; // Ajouter à la fin par défaut
            $studentData['is_active'] = true;
            
            // Combiner nom + prénom pour le champ legacy 'name'
            if (!empty($studentData['last_name']) && !empty($studentData['first_name'])) {
                $studentData['name'] = $studentData['last_name'] . ' ' . $studentData['first_name'];
            }

            $student = Student::create($studentData);
            $student->load(['schoolYear', 'classSeries']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $student,
                'message' => 'Élève créé avec succès'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'élève',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un élève
     */
    public function update(Request $request, Student $student)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'place_of_birth' => 'required|string|max:255',
            'gender' => 'required|in:M,F',
            'parent_name' => 'required|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'parent_email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'class_series_id' => 'required|exists:class_series,id',
            'school_year_id' => 'required|exists:school_years,id',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->all();
            
            // Combiner nom + prénom pour le champ legacy 'name'
            if (!empty($updateData['last_name']) && !empty($updateData['first_name'])) {
                $updateData['name'] = $updateData['last_name'] . ' ' . $updateData['first_name'];
            }
            
            $student->update($updateData);
            $student->load(['schoolYear', 'classSeries']);

            return response()->json([
                'success' => true,
                'data' => $student,
                'message' => 'Élève mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'élève',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un élève
     */
    public function destroy(Student $student)
    {
        try {
            $student->delete();

            return response()->json([
                'success' => true,
                'message' => 'Élève supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'élève',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter la liste des élèves en CSV
     */
    public function exportCsv($seriesId)
    {
        try {
            $currentYear = SchoolYear::where('is_current', true)->first();
            if (!$currentYear) {
                $currentYear = SchoolYear::where('is_active', true)->first();
            }
            
            if (!$currentYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            $students = Student::with(['schoolYear', 'classSeries'])
                ->forSeries($seriesId)
                ->forYear($currentYear->id)
                ->active()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            $series = ClassSeries::with(['schoolClass.level.section'])->find($seriesId);

            $filename = 'eleves_' . str_replace(' ', '_', $series->name) . '_' . date('Y-m-d') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function() use ($students) {
                $file = fopen('php://output', 'w');
                
                // Headers CSV
                fputcsv($file, [
                    'Numéro',
                    'Nom',
                    'Prénom', 
                    'Date de naissance',
                    'Lieu de naissance',
                    'Sexe',
                    'Nom du parent',
                    'Téléphone parent',
                    'Email parent',
                    'Adresse'
                ], ';');

                // Données
                foreach ($students as $student) {
                    fputcsv($file, [
                        $student->student_number,
                        $student->last_name,
                        $student->first_name,
                        $student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '',
                        $student->place_of_birth,
                        $student->gender === 'M' ? 'Masculin' : 'Féminin',
                        $student->parent_name,
                        $student->parent_phone,
                        $student->parent_email,
                        $student->address
                    ], ';');
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export CSV',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter la liste des élèves en PDF
     */
    public function exportPdf($seriesId)
    {
        try {
            $currentYear = SchoolYear::where('is_current', true)->first();
            if (!$currentYear) {
                $currentYear = SchoolYear::where('is_active', true)->first();
            }
            
            if (!$currentYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire définie'
                ], 400);
            }

            $students = Student::with(['schoolYear', 'classSeries'])
                ->forSeries($seriesId)
                ->forYear($currentYear->id)
                ->active()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            $series = ClassSeries::with(['schoolClass.level.section'])->find($seriesId);

            // Générer le HTML pour le PDF
            $html = $this->generateStudentListHtml($students, $series, $currentYear);
            
            $filename = 'eleves_' . str_replace(' ', '_', $series->name) . '_' . date('Y-m-d') . '.pdf';
            
            // Générer le HTML optimisé pour impression/PDF
            $optimizedHtml = $this->generatePdfFromHtml($html, $filename);
            
            // Retourner le HTML formaté que le navigateur peut imprimer en PDF
            return response($optimizedHtml, 200, [
                'Content-Type' => 'text/html',
                'Content-Disposition' => "inline; filename=\"students_list.html\"",
                'X-Suggested-Filename' => $filename
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le HTML pour la liste des élèves
     */
    private function generateStudentListHtml($students, $series, $schoolYear)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Liste des Élèves - ' . $series->name . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #2c3e50; margin-bottom: 5px; }
        .header p { color: #7f8c8d; margin: 5px 0; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .info-row { display: flex; justify-content: space-between; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #3498db; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .student-number { font-weight: bold; color: #2c3e50; }
        .gender-m { background-color: #e3f2fd; color: #1976d2; }
        .gender-f { background-color: #fce4ec; color: #c2185b; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #7f8c8d; }
        @media print {
            body { margin: 10px; }
            .header h1 { font-size: 18px; }
            th, td { padding: 5px; font-size: 10px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>GROUPE SCOLAIRE BILINGUE PRIVÉ LA SEMENCE</h1>
        <p>Liste des Élèves</p>
    </div>
    
    <div class="info-box">
        <div class="info-row">
            <strong>Série:</strong> <span>' . $series->name . '</span>
        </div>
        <div class="info-row">
            <strong>Classe:</strong> <span>' . $series->schoolClass->name . '</span>
        </div>
        <div class="info-row">
            <strong>Niveau:</strong> <span>' . $series->schoolClass->level->name . '</span>
        </div>
        <div class="info-row">
            <strong>Section:</strong> <span>' . $series->schoolClass->level->section->name . '</span>
        </div>
        <div class="info-row">
            <strong>Année scolaire:</strong> <span>' . $schoolYear->name . '</span>
        </div>
        <div class="info-row">
            <strong>Nombre d\'élèves:</strong> <span>' . $students->count() . '</span>
        </div>
        <div class="info-row">
            <strong>Date d\'export:</strong> <span>' . date('d/m/Y à H:i') . '</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">N°</th>
                <th style="width: 25%;">Nom & Prénom</th>
                <th style="width: 12%;">Date naiss.</th>
                <th style="width: 15%;">Lieu naiss.</th>
                <th style="width: 8%;">Sexe</th>
                <th style="width: 20%;">Parent</th>
                <th style="width: 12%;">Téléphone</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($students as $index => $student) {
            $html .= '<tr>
                <td class="student-number">' . ($index + 1) . '</td>
                <td><strong>' . $student->last_name . ' ' . $student->first_name . '</strong></td>
                <td>' . ($student->date_of_birth ? $student->date_of_birth->format('d/m/Y') : '') . '</td>
                <td>' . $student->place_of_birth . '</td>
                <td class="' . ($student->gender === 'M' ? 'gender-m' : 'gender-f') . '">' . 
                    ($student->gender === 'M' ? 'M' : 'F') . '</td>
                <td>' . $student->parent_name . '</td>
                <td>' . $student->parent_phone . '</td>
            </tr>';
        }

        $html .= '</tbody>
    </table>
    
    <div class="footer">
        <p>Document généré automatiquement le ' . date('d/m/Y à H:i:s') . '</p>
        <p>Groupe Scolaire Bilingue Privé La Semence</p>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Générer un PDF à partir du HTML (méthode simple mais robuste)
     */
    private function generatePdfFromHtml($html, $filename)
    {
        // Approche simple et robuste : retourner directement le HTML formaté pour impression
        // Le navigateur peut ensuite imprimer en PDF si nécessaire
        
        // Ajouter des styles optimisés pour l'impression/PDF
        $printOptimizedHtml = str_replace(
            '<style>',
            '<style>
                @page { 
                    size: A4; 
                    margin: 10mm; 
                }
                body { 
                    font-family: Arial, sans-serif; 
                    font-size: 10pt; 
                    line-height: 1.3;
                    margin: 0;
                    padding: 0;
                }
                .no-print { display: none !important; }
                table { 
                    page-break-inside: avoid; 
                    width: 100%;
                    font-size: 9pt;
                }
                thead { 
                    display: table-header-group; 
                }
                tr { 
                    page-break-inside: avoid; 
                }
            ',
            $html
        );
        
        // Retourner le HTML optimisé - le navigateur se chargera de la conversion PDF
        return $printOptimizedHtml;
    }

    /**
     * Importer des élèves depuis un fichier CSV
     */
    public function importCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt',
            'class_series_id' => 'required|exists:class_series,id',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $csvData = array_map(function($line) {
                return str_getcsv($line, ';', '"', '\\');
            }, file($file->getRealPath()));
            
            // Ignorer la première ligne (headers)
            $headers = array_shift($csvData);
            
            $imported = 0;
            $errors = [];
            $schoolYear = SchoolYear::find($request->school_year_id);

            DB::beginTransaction();

            foreach ($csvData as $index => $row) {
                if (count($row) < 6) { // Minimum requis
                    $errors[] = "Ligne " . ($index + 2) . ": données insuffisantes";
                    continue;
                }

                try {
                    $studentNumber = Student::generateStudentNumber(
                        $schoolYear->start_date, 
                        $request->class_series_id
                    );

                    $lastName = trim($row[0] ?? '');
                    $firstName = trim($row[1] ?? '');
                    
                    Student::create([
                        'last_name' => $lastName,
                        'first_name' => $firstName,
                        'name' => $lastName . ' ' . $firstName, // Combiner pour compatibilité
                        'date_of_birth' => $row[2] ? date('Y-m-d', strtotime($row[2])) : null,
                        'place_of_birth' => trim($row[3] ?? ''),
                        'gender' => strtoupper(trim($row[4] ?? '')) === 'M' ? 'M' : 'F',
                        'parent_name' => trim($row[5] ?? ''),
                        'parent_phone' => trim($row[6] ?? null),
                        'parent_email' => trim($row[7] ?? null),
                        'address' => trim($row[8] ?? null),
                        'class_series_id' => $request->class_series_id,
                        'school_year_id' => $request->school_year_id,
                        'student_number' => $studentNumber,
                        'is_active' => true
                    ]);
                    
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Ligne " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "$imported élève(s) importé(s) avec succès",
                'data' => [
                    'imported' => $imported,
                    'errors' => $errors
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les années scolaires disponibles
     */
    public function getSchoolYears()
    {
        try {
            $years = SchoolYear::where('is_active', true)
                ->orderBy('start_date', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $years
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getSchoolYears: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des années scolaires',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réorganiser les élèves par drag & drop
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'students' => 'required|array',
            'students.*.id' => 'required|exists:students,id',
            'students.*.order' => 'required|integer|min:1',
            'class_series_id' => 'required|exists:class_series,id',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($request->students as $studentData) {
                Student::where('id', $studentData['id'])
                    ->where('class_series_id', $request->class_series_id)
                    ->where('school_year_id', $request->school_year_id)
                    ->update(['order' => $studentData['order']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ordre des élèves mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réorganisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reclasser les élèves par ordre alphabétique
     */
    public function sortAlphabetically(Request $request, $seriesId)
    {
        $validator = Validator::make($request->all(), [
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Récupérer les élèves de la série et année scolaire
            $students = Student::where('class_series_id', $seriesId)
                ->where('school_year_id', $request->school_year_id)
                ->where('is_active', true)
                ->orderByRaw('COALESCE(last_name, name) ASC')
                ->orderByRaw('COALESCE(first_name, subname) ASC')
                ->get();

            // Assigner les nouveaux ordres
            foreach ($students as $index => $student) {
                $student->update(['order' => $index + 1]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Élèves reclassés par ordre alphabétique avec succès',
                'data' => $students->load(['schoolYear', 'classSeries'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du reclassement alphabétique',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}