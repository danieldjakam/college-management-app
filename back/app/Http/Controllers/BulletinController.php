<?php

namespace App\Http\Controllers;

use App\Models\BulletinGeneration;
use App\Models\BulletinTemplate;
use App\Models\Student;
use App\Models\Sequence;
use App\Models\Trimester;
use App\Models\Grade;
use App\Services\BulletinService;
use App\Services\BulletinAutoGenerationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BulletinController extends Controller
{
    protected $bulletinService;
    
    public function __construct(BulletinService $bulletinService)
    {
        $this->bulletinService = $bulletinService;
    }
    
    /**
     * Get available bulletins for a student
     */
    public function availableBulletins($studentId)
    {
        $student = Student::findOrFail($studentId);
        
        $availableBulletins = [];
        
        // Check sequence bulletins (1 and 3 only)
        $sequences = Sequence::where('number', 1)
                            ->orWhere('number', 3)
                            ->where('is_completed', true)
                            ->get();
                            
        foreach ($sequences as $sequence) {
            $existing = BulletinGeneration::byStudent($studentId)
                                         ->byPeriod('sequence', 'seq' . $sequence->number)
                                         ->first();
            
            $availableBulletins[] = [
                'type' => 'sequence',
                'identifier' => 'seq' . $sequence->number,
                'name' => 'Bulletin Séquence ' . $sequence->number,
                'available' => true,
                'generated' => $existing ? true : false,
                'file_path' => $existing ? $existing->file_path : null
            ];
        }
        
        // Check trimester bulletins
        $trimesters = Trimester::whereHas('sequences', function($query) {
                               $query->where('is_completed', true);
                             })->get();
                             
        foreach ($trimesters as $trimester) {
            $existing = BulletinGeneration::byStudent($studentId)
                                         ->byPeriod('trimester', 'trim' . $trimester->number)
                                         ->first();
                                         
            $availableBulletins[] = [
                'type' => 'trimester',
                'identifier' => 'trim' . $trimester->number,
                'name' => 'Bulletin Trimestre ' . $trimester->number,
                'available' => true,
                'generated' => $existing ? true : false,
                'file_path' => $existing ? $existing->file_path : null
            ];
        }
        
        return response()->json([
            'student' => $student,
            'bulletins' => $availableBulletins
        ]);
    }
    
    /**
     * Generate a bulletin
     */
    public function generate(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'bulletin_type' => 'required|in:sequence,trimester,annual,honor_roll',
            'period_identifier' => 'required|string'
        ]);
        
        $student = Student::findOrFail($request->student_id);
        
        // We no longer need a template from database, we use the CPBD template file directly
        // But we need a fallback template ID for the database record
        $template = BulletinTemplate::where('type', $request->bulletin_type)->active()->first();
        if (!$template) {
            // Create a fallback template if none exists
            $template = BulletinTemplate::firstOrCreate(
                ['type' => $request->bulletin_type, 'name' => 'CPBD Template'],
                [
                    'name' => 'CPBD Template',
                    'type' => $request->bulletin_type,
                    'template_html' => 'Default CPBD Template',
                    'is_active' => true,
                    'description' => 'Template par défaut CPBD'
                ]
            );
        }
        
        // Check if bulletin already exists
        $existing = BulletinGeneration::byStudent($request->student_id)
                                     ->byPeriod($request->bulletin_type, $request->period_identifier)
                                     ->first();
                                     
        if ($existing) {
            return response()->json(['error' => 'Bulletin already generated'], 409);
        }
        
        try {
            // Generate bulletin data based on type
            $bulletinData = null;
            $filename = null;
            
            if ($request->bulletin_type === 'sequence') {
                $sequenceNumber = (int) str_replace('seq', '', $request->period_identifier);
                $bulletinData = $this->bulletinService->generateSequenceBulletinData($sequenceNumber, $request->student_id);
                $filename = "bulletin_sequence_{$sequenceNumber}_{$request->student_id}_" . now()->format('Y-m-d') . ".pdf";
            } elseif ($request->bulletin_type === 'trimester') {
                $trimesterNumber = (int) str_replace('trim', '', $request->period_identifier);
                $bulletinData = $this->bulletinService->generateTrimesterBulletinData($trimesterNumber, $request->student_id);
                $filename = "bulletin_trimestre_{$trimesterNumber}_{$request->student_id}_" . now()->format('Y-m-d') . ".pdf";
            }
            
            if (!$bulletinData) {
                return response()->json(['error' => 'Unable to generate bulletin data'], 500);
            }
            
            // Render HTML template with data (use PDF-optimized template)
            $htmlContent = $this->bulletinService->renderBulletinTemplate($request->bulletin_type, $bulletinData, true);
            
            // Generate PDF
            $filePath = $this->bulletinService->generatePDF($htmlContent, $filename);
            
            // Create the record
            $bulletinGeneration = BulletinGeneration::create([
                'student_id' => $request->student_id,
                'template_id' => $template->id,
                'period_type' => $request->bulletin_type,
                'period_identifier' => $request->period_identifier,
                'file_path' => $filePath,
                'generated_at' => now(),
                'is_complete' => true,
                'completion_percentage' => 100.0
            ]);
            
            return response()->json([
                'message' => 'Bulletin generated successfully',
                'bulletin' => $bulletinGeneration,
                'download_url' => route('bulletins.download', $bulletinGeneration->id)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error generating bulletin: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Download a bulletin
     */
    public function download($bulletinId)
    {
        $bulletin = BulletinGeneration::findOrFail($bulletinId);
        
        if (!$bulletin->file_path || !file_exists(storage_path('app/' . $bulletin->file_path))) {
            return response()->json(['error' => 'Bulletin file not found'], 404);
        }
        
        return response()->download(storage_path('app/' . $bulletin->file_path));
    }
    
    /**
     * Generate bulletins for entire class
     */
    public function batchGenerate(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'bulletin_type' => 'required|in:sequence,trimester,annual,honor_roll',
            'period_identifier' => 'required|string'
        ]);
        
        $students = Student::where('school_class_id', $request->class_id)->get();
        $generated = [];
        $errors = [];
        
        foreach ($students as $student) {
            try {
                $generateRequest = new Request([
                    'student_id' => $student->id,
                    'bulletin_type' => $request->bulletin_type,
                    'period_identifier' => $request->period_identifier
                ]);
                
                $result = $this->generate($generateRequest);
                $generated[] = $student->id;
            } catch (\Exception $e) {
                $errors[] = [
                    'student_id' => $student->id,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return response()->json([
            'message' => 'Batch generation completed',
            'generated_count' => count($generated),
            'error_count' => count($errors),
            'generated' => $generated,
            'errors' => $errors
        ]);
    }
    
    /**
     * Get all templates (Admin only)
     */
    public function getTemplates()
    {
        $templates = BulletinTemplate::all();
        return response()->json($templates);
    }
    
    /**
     * Create new template (Admin only)
     */
    public function createTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:sequence,trimester,annual,honor_roll',
            'template_html' => 'required|string',
            'css_styles' => 'nullable|string',
            'description' => 'nullable|string'
        ]);
        
        $template = BulletinTemplate::create($request->all());
        
        return response()->json([
            'message' => 'Template created successfully',
            'template' => $template
        ], 201);
    }
    
    /**
     * Update template (Admin only)
     */
    public function updateTemplate(Request $request, $templateId)
    {
        $template = BulletinTemplate::findOrFail($templateId);
        
        $request->validate([
            'name' => 'string|max:255',
            'type' => 'in:sequence,trimester,annual,honor_roll',
            'template_html' => 'string',
            'css_styles' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);
        
        $template->update($request->all());
        
        return response()->json([
            'message' => 'Template updated successfully',
            'template' => $template
        ]);
    }
    
    /**
     * Delete template (Admin only)
     */
    public function deleteTemplate($templateId)
    {
        $template = BulletinTemplate::findOrFail($templateId);
        $template->delete();
        
        return response()->json([
            'message' => 'Template deleted successfully'
        ]);
    }
    
    /**
     * Toggle template status (Admin only)
     */
    public function toggleTemplateStatus($templateId)
    {
        $template = BulletinTemplate::findOrFail($templateId);
        $template->update(['is_active' => !$template->is_active]);
        
        return response()->json([
            'message' => 'Template status updated successfully',
            'template' => $template
        ]);
    }
    
    /**
     * Get all generated bulletins for admin view
     */
    public function getGeneratedBulletins(Request $request)
    {
        $query = BulletinGeneration::with(['student.schoolClass', 'template'])
                                  ->orderBy('generated_at', 'desc');
        
        // Apply filters
        if ($request->has('class_id') && $request->class_id) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('school_class_id', $request->class_id);
            });
        }
        
        if ($request->has('period_type') && $request->period_type) {
            $query->where('period_type', $request->period_type);
        }
        
        if ($request->has('period_identifier') && $request->period_identifier) {
            $query->where('period_identifier', $request->period_identifier);
        }
        
        $bulletins = $query->paginate(50);
        
        return response()->json($bulletins);
    }

    /**
     * Get hierarchical structure for bulletin management
     */
    public function getHierarchicalStructure()
    {
        $sections = \App\Models\Section::with([
            'levels.schoolClasses.series' => function($query) {
                $query->whereHas('students');
            }
        ])->get();

        return response()->json([
            'success' => true,
            'data' => $sections
        ]);
    }

    /**
     * Get students with bulletin completion status for a class series
     * Avec support pour navigation temporelle (voir périodes passées/futures)
     */
    public function getStudentsBulletinStatus($seriesId, Request $request)
    {
        $series = \App\Models\ClassSeries::with('schoolClass')->find($seriesId);
        if (!$series) {
            return response()->json(['error' => 'Series not found'], 404);
        }

        $students = \App\Models\Student::where('class_series_id', $seriesId)
            ->with(['schoolClass'])
            ->get();


        // Paramètre pour filtrer par période spécifique (optionnel)
        $viewPeriod = $request->get('period'); // ex: 'seq1', 'trim1', 'current', 'all'
        
        $studentsWithStatus = [];
        
        foreach ($students as $student) {
            $studentData = [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'matricule' => $student->matricule,
                'bulletins' => []
            ];

            // Vérifier les bulletins de séquence (1 et 3)
            foreach ([1, 3] as $seqNumber) {
                $completion = $this->calculateSequenceCompletion($student->id, $seqNumber);
                $bulletin = BulletinGeneration::where('student_id', $student->id)
                    ->where('period_type', 'sequence')
                    ->where('period_identifier', "seq{$seqNumber}")
                    ->first();

                $sequence = \App\Models\Sequence::where('number', $seqNumber)->first();
                $status = $this->getSequenceStatus($seqNumber);

                $studentData['bulletins']["sequence_{$seqNumber}"] = [
                    'type' => 'sequence',
                    'identifier' => "seq{$seqNumber}",
                    'name' => "Séquence {$seqNumber}",
                    'completion_percentage' => $completion,
                    'is_generated' => $bulletin ? true : false,
                    'bulletin_id' => $bulletin ? $bulletin->id : null,
                    'generated_at' => $bulletin ? $bulletin->generated_at : null,
                    'status' => $status, // 'past', 'current', 'future'
                    'can_preview' => true, // Toujours permettre la prévisualisation
                    'is_archived' => $status === 'past'
                ];
            }

            // Vérifier les bulletins de trimestre
            for ($trimNumber = 1; $trimNumber <= 3; $trimNumber++) {
                $completion = $this->calculateTrimesterCompletion($student->id, $trimNumber);
                $bulletin = BulletinGeneration::where('student_id', $student->id)
                    ->where('period_type', 'trimester')
                    ->where('period_identifier', "trim{$trimNumber}")
                    ->first();

                $status = $this->getTrimesterStatus($trimNumber);

                $studentData['bulletins']["trimester_{$trimNumber}"] = [
                    'type' => 'trimester',
                    'identifier' => "trim{$trimNumber}",
                    'name' => "Trimestre {$trimNumber}",
                    'completion_percentage' => $completion,
                    'is_generated' => $bulletin ? true : false,
                    'bulletin_id' => $bulletin ? $bulletin->id : null,
                    'generated_at' => $bulletin ? $bulletin->generated_at : null,
                    'status' => $status, // 'past', 'current', 'future'
                    'can_preview' => true, // Toujours permettre les téléchargements trimestre (affiche 0 pour données manquantes)
                    'is_archived' => $status === 'past'
                ];
            }

            $studentsWithStatus[] = $studentData;
        }

        // Informations pour le sélecteur de période
        $availablePeriods = $this->getAvailablePeriods();

        return response()->json([
            'success' => true,
            'series' => $series,
            'students' => $studentsWithStatus,
            'available_periods' => $availablePeriods,
            'current_view_period' => $viewPeriod ?: 'current'
        ]);
    }

    /**
     * Calculate sequence completion percentage for a student
     * Logique académique: seules les séquences 1 et 3 génèrent des bulletins
     */
    private function calculateSequenceCompletion($studentId, $sequenceNumber)
    {
        $student = \App\Models\Student::find($studentId);
        if (!$student || !$student->schoolClass) return 0;

        $subjects = \App\Models\SeriesSubject::where('school_class_id', $student->schoolClass->id)->get();
        if ($subjects->count() === 0) return 0;

        $sequence = \App\Models\Sequence::where('number', $sequenceNumber)->first();
        if (!$sequence) return 0;
        
        // Logique de reset: si la séquence est terminée, garder 100%
        // Si la séquence n'est pas encore active, 0%
        // Si la séquence est active, calculer le pourcentage réel
        
        $currentActiveSequence = \App\Models\Sequence::where('is_active', true)->first();
        
        if ($sequence->is_completed) {
            // Séquence terminée -> garder le statut à 100% si un bulletin existe
            $existingBulletin = \App\Models\BulletinGeneration::where('student_id', $studentId)
                ->where('period_type', 'sequence')
                ->where('period_identifier', "seq{$sequenceNumber}")
                ->first();
            return $existingBulletin ? 100 : 0;
        }
        
        if ($currentActiveSequence && $currentActiveSequence->number > $sequenceNumber) {
            // Séquence passée -> garder le statut final
            $existingBulletin = \App\Models\BulletinGeneration::where('student_id', $studentId)
                ->where('period_type', 'sequence')
                ->where('period_identifier', "seq{$sequenceNumber}")
                ->first();
            return $existingBulletin ? 100 : 0;
        }
        
        if ($currentActiveSequence && $currentActiveSequence->number < $sequenceNumber) {
            // Séquence future -> 0%
            return 0;
        }

        // Séquence actuelle -> calculer le pourcentage réel
        $gradedSubjects = 0;
        foreach ($subjects as $subject) {
            $hasGrade = \App\Models\Grade::where('student_id', $studentId)
                ->where('sequence_id', $sequence->id)
                ->where('series_subject_id', $subject->id)
                ->whereNotNull('score')
                ->exists();
            
            if ($hasGrade) {
                $gradedSubjects++;
            }
        }

        return round(($gradedSubjects / $subjects->count()) * 100, 1);
    }

    /**
     * Calculate trimester completion percentage for a student
     * Logique académique: DS1=(Seq1+Seq2)/2, DS2=(Seq3+Seq4)/2, Trimestre=(DS+Composition)/2
     */
    private function calculateTrimesterCompletion($studentId, $trimesterNumber)
    {
        try {
            $student = \App\Models\Student::find($studentId);
            if (!$student || !$student->schoolClass) {
                return 0;
            }

            $subjects = \App\Models\SeriesSubject::where('school_class_id', $student->schoolClass->id)->get();
            if ($subjects->count() === 0) {
                return 0;
            }

        $totalCompletion = 0;
        $currentActiveSequence = \App\Models\Sequence::where('is_active', true)->first();
        
        foreach ($subjects as $subject) {
            if ($trimesterNumber == 3) {
                // Trimestre 3: Composition seule
                $compositionCompletion = $this->checkCompositionCompletion($studentId, 3, $subject->id);
                $subjectCompletion = $compositionCompletion;
            } else {
                // Trimestre 1 ou 2: (DS + Composition) / 2
                $dsCompletion = $this->checkDSCompletion($studentId, $trimesterNumber, $subject->id);
                $compositionCompletion = $this->checkCompositionCompletion($studentId, $trimesterNumber, $subject->id);
                
                // Logique de mise à jour pendant les séquences:
                // - Trimestre 1 se met à jour pendant séquences 1 et 2
                // - Trimestre 2 se met à jour pendant séquences 3 et 4
                
                if ($trimesterNumber == 1) {
                    // Pendant séquence 2, le trimestre 1 se calcule déjà avec séq1+séq2
                    if ($currentActiveSequence && $currentActiveSequence->number == 2) {
                        // On est en train de saisir la séquence 2 -> calculer DS1 avec séq1+séq2
                        $dsCompletion = $this->checkDSCompletion($studentId, 1, $subject->id);
                    }
                } elseif ($trimesterNumber == 2) {
                    // Pendant séquence 4, le trimestre 2 se calcule avec séq3+séq4
                    if ($currentActiveSequence && $currentActiveSequence->number == 4) {
                        $dsCompletion = $this->checkDSCompletion($studentId, 2, $subject->id);
                    }
                }
                
                $subjectCompletion = ($dsCompletion + $compositionCompletion) / 2;
            }
            
            $totalCompletion += $subjectCompletion;
        }

            $finalCompletion = round($totalCompletion / $subjects->count(), 1);
            return $finalCompletion;
        } catch (\Exception $e) {
            \Log::error("ERROR in calculateTrimesterCompletion for student {$studentId}, trimester {$trimesterNumber}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check DS completion for trimester
     */
    private function checkDSCompletion($studentId, $trimesterNumber, $subjectId)
    {
        
        $sequenceNumbers = [];
        
        switch ($trimesterNumber) {
            case 1:
                $sequenceNumbers = [1, 2];
                break;
            case 2:
                $sequenceNumbers = [3, 4];
                break;
            default:
                return 100; // Trimestre 3 n'a pas de DS
        }
        
        // Prendre seulement une séquence par numéro pour éviter les doublons
        $sequences = collect();
        foreach ($sequenceNumbers as $number) {
            $seq = \App\Models\Sequence::where('number', $number)->first();
            if ($seq) {
                $sequences->push($seq);
            }
        }
        $gradedSequences = 0;
        
        foreach ($sequences as $sequence) {
            $hasGrade = \App\Models\Grade::where('student_id', $studentId)
                ->where('sequence_id', $sequence->id)
                ->where('series_subject_id', $subjectId)
                ->where('trimester_id', $trimesterNumber)
                ->whereNotNull('score')
                ->exists();
            
            
            if ($hasGrade) {
                $gradedSequences++;
            }
        }
        
        $completion = $sequences->count() > 0 ? ($gradedSequences / $sequences->count()) * 100 : 0;
        return $completion;
    }

    /**
     * Check composition completion
     */
    private function checkCompositionCompletion($studentId, $trimesterNumber, $subjectId)
    {
        
        $evaluation = \App\Models\Evaluation::where('type', 'composition')
            ->where('trimester_id', $trimesterNumber)
            ->where('series_subject_id', $subjectId)
            ->first();
        
        
        if (!$evaluation) {
            return 0;
        }
        
        $grade = \App\Models\Grade::where('student_id', $studentId)
            ->where('evaluation_id', $evaluation->id)
            ->where('trimester_id', $trimesterNumber)
            ->whereNotNull('score')
            ->exists();
        
        $completion = $grade ? 100 : 0;
        \Log::info("🔍 Composition completion: " . ($grade ? 'YES' : 'NO') . " = {$completion}%");
        return $completion;
    }

    /**
     * Get current academic timeline
     */
    public function getAcademicTimeline()
    {
        $sequences = \App\Models\Sequence::orderBy('number')->get();
        $trimesters = \App\Models\Trimester::orderBy('number')->get();
        
        // Déterminer la période actuelle basée sur les séquences actives
        $currentSequence = $sequences->where('is_active', true)->first();
        $currentTrimester = null;
        
        if ($currentSequence) {
            $currentTrimester = $trimesters->find($currentSequence->trimester_id);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'sequences' => $sequences,
                'trimesters' => $trimesters,
                'current_sequence' => $currentSequence,
                'current_trimester' => $currentTrimester,
                'school_year' => date('Y') . '/' . (date('Y') + 1)
            ]
        ]);
    }

    /**
     * Preview bulletin HTML
     */
    public function previewBulletin(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:sequence,trimester',
            'period_identifier' => 'required|string'
        ]);

        try {
            $bulletinData = null;
            
            if ($request->type === 'sequence') {
                $sequenceNumber = (int) str_replace('seq', '', $request->period_identifier);
                $bulletinData = $this->bulletinService->generateSequenceBulletinData($sequenceNumber, $request->student_id);
            } elseif ($request->type === 'trimester') {
                $trimesterNumber = (int) str_replace('trim', '', $request->period_identifier);
                $bulletinData = $this->bulletinService->generateTrimesterBulletinData($trimesterNumber, $request->student_id);
            }

            if (!$bulletinData) {
                return response()->json(['error' => 'Impossible de générer les données du bulletin'], 500);
            }

            $htmlContent = $this->bulletinService->renderBulletinTemplate($request->type, $bulletinData);

            return response()->json([
                'success' => true,
                'html' => $htmlContent,
                'data' => $bulletinData
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Force regeneration of a bulletin
     */
    public function forceRegenerate(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'period_type' => 'required|in:sequence,trimester,annual,honor_roll',
            'period_identifier' => 'required|string'
        ]);
        
        try {
            // Delete existing bulletin
            BulletinGeneration::where('student_id', $request->student_id)
                              ->where('period_type', $request->period_type)
                              ->where('period_identifier', $request->period_identifier)
                              ->delete();
            
            // Trigger regeneration via the auto-generation service
            $autoGenerationService = new BulletinAutoGenerationService($this->bulletinService);
            
            // Find a grade for this student to trigger the generation
            $grade = Grade::where('student_id', $request->student_id)->first();
            if ($grade) {
                $autoGenerationService->checkAndGenerateBulletins($grade->id);
            }
            
            return response()->json([
                'message' => 'Bulletin regeneration triggered successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error during regeneration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download all bulletins for a class series as a ZIP file
     */
    public function downloadAllBulletins(Request $request)
    {
        $request->validate([
            'series_id' => 'required|exists:class_series,id',
            'period_type' => 'nullable|string',
            'period_identifier' => 'nullable|string'
        ]);

        try {
            $series = \App\Models\ClassSeries::with('schoolClass')->findOrFail($request->series_id);
            
            // First, trigger auto-generation for all students in this series
            $students = \App\Models\Student::where('class_series_id', $request->series_id)->get();
            $autoGenerationService = new BulletinAutoGenerationService($this->bulletinService);
            
            $generatedCount = 0;
            foreach ($students as $student) {
                try {
                    // Find any grade for this student to trigger generation
                    $grade = Grade::where('student_id', $student->id)->first();
                    if ($grade) {
                        $autoGenerationService->checkAndGenerateBulletins($grade->id);
                        $generatedCount++;
                    }
                } catch (\Exception $e) {
                    \Log::error("Error generating bulletin for student {$student->id}: " . $e->getMessage());
                    continue;
                }
            }
            
            // Wait only if we generated something
            if ($generatedCount > 0) {
                sleep(1);
            }
            
            // Get all generated bulletins for this series
            $query = BulletinGeneration::whereHas('student', function($q) use ($request) {
                $q->where('class_series_id', $request->series_id);
            })->where('is_complete', true);

            // Filter by period if specified
            if ($request->period_type && $request->period_identifier) {
                $query->where('period_type', $request->period_type)
                      ->where('period_identifier', $request->period_identifier);
            }

            $bulletins = $query->with('student')->get();

            if ($bulletins->isEmpty()) {
                // Try to find students without bulletins for debugging
                $studentsWithoutBulletins = \App\Models\Student::where('class_series_id', $request->series_id)
                    ->whereDoesntHave('bulletinGenerations', function($q) {
                        $q->where('is_complete', true);
                    })->count();
                
                return response()->json([
                    'error' => 'Aucun bulletin généré trouvé',
                    'debug' => [
                        'students_in_series' => $students->count(),
                        'students_without_bulletins' => $studentsWithoutBulletins,
                        'generated_count' => $generatedCount,
                        'series_name' => $series->name
                    ]
                ], 404);
            }

            // Create a temporary directory for the ZIP
            $tempDir = storage_path('app/temp/bulletins_' . time());
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $validFiles = [];

            foreach ($bulletins as $bulletin) {
                // Use the file_path field from the database
                $pdfPath = storage_path('app/' . $bulletin->file_path);
                
                if (file_exists($pdfPath)) {
                    $student = $bulletin->student;
                    $fileName = sprintf(
                        'bulletin_%s_%s_%s_%s.pdf',
                        $bulletin->period_type,
                        $bulletin->period_identifier,
                        $student->first_name,
                        $student->last_name
                    );
                    
                    $destinationPath = $tempDir . '/' . $fileName;
                    copy($pdfPath, $destinationPath);
                    $validFiles[] = $fileName;
                }
            }

            if (empty($validFiles)) {
                return response()->json(['error' => 'Aucun fichier PDF valide trouvé'], 404);
            }

            // Create ZIP file
            $zipFileName = sprintf(
                'bulletins_%s_%s_%s.zip',
                $series->schoolClass->name,
                $series->name,
                date('Y-m-d_H-i-s')
            );
            $zipPath = storage_path('app/temp/' . $zipFileName);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
                return response()->json(['error' => 'Impossible de créer le fichier ZIP'], 500);
            }

            // Add files to ZIP
            foreach ($validFiles as $fileName) {
                $filePath = $tempDir . '/' . $fileName;
                $zip->addFile($filePath, $fileName);
            }

            $zip->close();

            // Clean up temporary directory
            array_map('unlink', glob($tempDir . '/*'));
            rmdir($tempDir);

            // Return the ZIP file
            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la création du ZIP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Détermine le statut d'une séquence (past, current, future)
     */
    private function getSequenceStatus($sequenceNumber)
    {
        $sequence = \App\Models\Sequence::where('number', $sequenceNumber)
            ->where('is_composition', false) // Exclure les compositions
            ->first();
        
        if (!$sequence) return 'future';
        
        // Priorité à is_current
        if ($sequence->is_current) return 'current';
        if ($sequence->is_completed) return 'past';
        if ($sequence->is_active && !$sequence->is_completed) return 'current';
        
        // Si pas trouvé de current, utiliser la logique globale
        $currentSequence = \App\Models\Sequence::where('is_current', true)->first();
        if ($currentSequence && !$currentSequence->is_composition) {
            if ($currentSequence->number > $sequenceNumber) return 'past';
            if ($currentSequence->number < $sequenceNumber) return 'future';
        }
        
        return 'future';
    }

    /**
     * Détermine le statut d'un trimestre (past, current, future)
     */
    private function getTrimesterStatus($trimesterNumber)
    {
        $currentActiveSequence = \App\Models\Sequence::where('is_active', true)->first();
        
        if (!$currentActiveSequence) return 'future';
        
        // Logique académique: 
        // Trimestre 1 = Séquences 1,2 + Composition 1
        // Trimestre 2 = Séquences 3,4 + Composition 2  
        // Trimestre 3 = Composition 3
        
        if ($trimesterNumber == 1) {
            if ($currentActiveSequence->number >= 2) return 'current'; // Pendant ou après séq 2
            if ($currentActiveSequence->number == 1) return 'current'; // Pendant séq 1
            return 'future';
        } elseif ($trimesterNumber == 2) {
            if ($currentActiveSequence->number >= 4) return 'current'; // Pendant ou après séq 4
            if ($currentActiveSequence->number == 3) return 'current'; // Pendant séq 3  
            if ($currentActiveSequence->number <= 2) return 'future';
        } elseif ($trimesterNumber == 3) {
            // Trimestre 3 disponible après séquence 4
            if ($currentActiveSequence->number > 4) return 'current';
            return 'future';
        }
        
        return 'future';
    }

    /**
     * Retourne toutes les périodes disponibles pour navigation
     */
    private function getAvailablePeriods()
    {
        $periods = [];
        
        // Séquences 1 et 3 (seules avec bulletins)
        foreach ([1, 3] as $seqNumber) {
            $sequence = \App\Models\Sequence::where('number', $seqNumber)->first();
            if ($sequence) {
                $status = $this->getSequenceStatus($seqNumber);
                $periods[] = [
                    'type' => 'sequence',
                    'identifier' => "seq{$seqNumber}",
                    'name' => "Séquence {$seqNumber}",
                    'status' => $status,
                    'icon' => $status === 'past' ? 'archive' : ($status === 'current' ? 'play-circle' : 'clock')
                ];
            }
        }
        
        // Trimestres 1, 2, 3
        for ($trimNumber = 1; $trimNumber <= 3; $trimNumber++) {
            $status = $this->getTrimesterStatus($trimNumber);
            $periods[] = [
                'type' => 'trimester', 
                'identifier' => "trim{$trimNumber}",
                'name' => "Trimestre {$trimNumber}",
                'status' => $status,
                'icon' => $status === 'past' ? 'archive' : ($status === 'current' ? 'play-circle' : 'clock')
            ];
        }
        
        // Option "Vue actuelle"
        $periods[] = [
            'type' => 'view',
            'identifier' => 'current',
            'name' => 'Vue Actuelle',
            'status' => 'current',
            'icon' => 'eye'
        ];
        
        // Option "Toutes les périodes"
        $periods[] = [
            'type' => 'view',
            'identifier' => 'all',
            'name' => 'Toutes les Périodes',
            'status' => 'all',
            'icon' => 'grid'
        ];
        
        return $periods;
    }
}
