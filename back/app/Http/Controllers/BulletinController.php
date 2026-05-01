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
use App\Services\BulletinCacheService;
use App\Jobs\GenerateBulletinBatch;
use App\Jobs\MergeBulletinPDFs;
use App\Models\MergedBulletinPDF;
use App\Models\ClassSeries;
use App\Models\BulletinModification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class BulletinController extends Controller
{
    protected $bulletinService;
    protected $cacheService;

    public function __construct(BulletinService $bulletinService, BulletinCacheService $cacheService)
    {
        $this->bulletinService = $bulletinService;
        $this->cacheService = $cacheService;
    }

    /**
     * Get available bulletins for a student
     */
    public function availableBulletins($studentId)
    {
        $student = Student::findOrFail($studentId);

        $availableBulletins = [];

        // 🎓 Déterminer le type de cycle
        $cycleType = $this->determineCycleType($student);

        // 📚 PREMIER CYCLE: Seulement séquences 1 et 3
        // 🎓 DEUXIÈME CYCLE: Toutes les séquences (1, 2, 3, 4)
        $allowedSequences = ($cycleType === 'deuxieme') ? [1, 2, 3, 4] : [1, 3];

        // Check sequence bulletins selon le cycle
        $sequences = Sequence::whereIn('number', $allowedSequences)
            ->where('is_completed', true)
            ->where('is_composition', false) // Exclure les compositions
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
        $trimesters = Trimester::whereHas('sequences', function ($query) {
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

        // Check annual bulletin (available for ALL classes)
        $existing = BulletinGeneration::byStudent($studentId)
            ->byPeriod('annual', 'annual')
            ->first();

        $availableBulletins[] = [
            'type' => 'annual',
            'identifier' => 'annual',
            'name' => 'Bulletin Annuel',
            'available' => true,
            'generated' => $existing ? true : false,
            'file_path' => $existing ? $existing->file_path : null
        ];

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
        // Augmenter la limite de mémoire pour la génération de bulletins
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '120'); // 2 minutes par bulletin

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'bulletin_type' => 'required|in:sequence,trimester,annual,honor_roll',
            'period_identifier' => 'required|string',
            'force' => 'nullable|boolean' // Permet de forcer la régénération
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

        if ($existing && !$request->input('force', false)) {
            return response()->json(['error' => 'Bulletin already generated. Use force=true to regenerate.'], 409);
        }

        // Si force=true et que le bulletin existe, le supprimer d'abord
        if ($existing && $request->input('force', false)) {
            // Supprimer le fichier PDF s'il existe
            if ($existing->file_path && file_exists(storage_path('app/' . $existing->file_path))) {
                unlink(storage_path('app/' . $existing->file_path));
            }
            // Supprimer l'enregistrement
            $existing->delete();
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
            } elseif ($request->bulletin_type === 'annual') {
                // Use APC or non-APC annual method based on class type
                if ($this->bulletinService->isApcClass($student)) {
                    $bulletinData = $this->bulletinService->generateAnnualBulletinData($request->student_id);
                } else {
                    $bulletinData = $this->bulletinService->generateAnnualBulletinDataNonApc($request->student_id);
                }
                $filename = "bulletin_annuel_{$request->student_id}_" . now()->format('Y-m-d') . ".pdf";
            }

            if (!$bulletinData) {
                return response()->json(['error' => 'Unable to generate bulletin data'], 500);
            }

            // Apply manual modifications if any
            $modification = BulletinModification::where('student_id', $request->student_id)
                ->where('period_type', $request->bulletin_type)
                ->where('period_identifier', $request->period_identifier)
                ->first();

            if ($modification) {
                $modController = new BulletinModificationController($this->bulletinService);
                $bulletinData = $modController->applyModifications($bulletinData, $modification->modifications, $request->bulletin_type);
            }

            // Vérifier si l'élève a des notes (rank ne doit pas être null)
            if ($bulletinData['rank'] === null || ($bulletinData['total_coefficient'] ?? 0) == 0) {
                return response()->json([
                    'error' => 'Cannot generate bulletin: student has no grades entered. Please enter grades before generating the bulletin.'
                ], 400);
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

            // Libérer la mémoire après génération
            gc_collect_cycles();

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
        // Vérifier si le bulletin existe en base de données
        $bulletin = BulletinGeneration::find($bulletinId);

        if (!$bulletin) {
            \Log::warning("Bulletin record not found in database", ['bulletin_id' => $bulletinId]);
            return response()->json([
                'success' => false,
                'message' => "Bulletin not found in database. Please regenerate it.",
                'bulletin_id' => $bulletinId
            ], 404);
        }

        // Vérifier plusieurs emplacements possibles pour la compatibilité
        $possiblePaths = [];

        if ($bulletin->file_path) {
            // 1. Chemin actuel (nouveau format)
            $possiblePaths[] = storage_path('app/' . $bulletin->file_path);

            // 2. Ancien format (bulletins/ sans public/)
            if (str_starts_with($bulletin->file_path, 'public/bulletins/')) {
                $oldPath = str_replace('public/bulletins/', 'bulletins/', $bulletin->file_path);
                $possiblePaths[] = storage_path('app/' . $oldPath);
            }
        }

        // Vérifier si un fichier existe
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return response()->download($path);
            }
        }

        // Aucun fichier trouvé - retourner 404 JSON
        \Log::warning("Bulletin PDF file not found on disk", [
            'bulletin_id' => $bulletinId,
            'file_path' => $bulletin->file_path,
            'checked_paths' => $possiblePaths
        ]);

        return response()->json([
            'success' => false,
            'message' => "Bulletin PDF file not found. Please regenerate it.",
            'bulletin_id' => $bulletinId,
            'student_id' => $bulletin->student_id,
            'file_path' => $bulletin->file_path
        ], 404);

        return response()->json([
            'error' => 'Bulletin file not found',
            'bulletin_id' => $bulletinId,
            'student_id' => $bulletin->student_id,
            'period_type' => $bulletin->period_type,
            'period_identifier' => $bulletin->period_identifier
        ], 404);
    }

    /**
     * NOTE: printAllClass() method was removed - use downloadAllBulletins() instead
     * downloadAllBulletins() already creates a ZIP with all PDFs for a class/period
     */

    /**
     * Helper method to prepare bulletin data
     */
    private function prepareBulletinData($studentId, $periodType, $periodIdentifier)
    {
        if ($periodType === 'sequence') {
            $sequenceNumber = (int) str_replace('seq', '', $periodIdentifier);
            return $this->bulletinService->generateSequenceBulletinData($sequenceNumber, $studentId);
        } elseif ($periodType === 'annual') {
            return $this->bulletinService->generateAnnualBulletinData($studentId);
        } else {
            $trimesterNumber = (int) str_replace('trim', '', $periodIdentifier);
            return $this->bulletinService->generateTrimesterBulletinData($trimesterNumber, $studentId);
        }
    }

    /**
     * Get batch generation progress
     * Returns real-time progress for bulletin generation
     */
    public function getBatchProgress($progressKey)
    {
        $progress = \Cache::get($progressKey);

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune progression trouvée pour cette clé',
                'progress' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'progress' => $progress
        ]);
    }

    /**
     * Generate bulletins for entire class (ASYNCHRONOUS VERSION)
     * Dispatches a background job and returns immediately with a progress key
     */
    public function batchGenerate(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'bulletin_type' => 'required|in:sequence,trimester,annual,honor_roll',
            'period_identifier' => 'required|string',
            'force' => 'nullable|boolean'
        ]);

        // Créer une clé de progression unique
        $progressKey = "bulletin_progress_{$request->class_id}_{$request->period_identifier}_" . time();

        // Initialiser le cache de progression
        Cache::put($progressKey, [
            'current' => 0,
            'total' => 0,
            'percentage' => 0,
            'status' => 'queued',
            'message' => 'Job mis en file d\'attente...',
            'started_at' => now()->toDateTimeString()
        ], 900);

        \Log::info("🚀 Dispatching bulletin generation job", [
            'class_id' => $request->class_id,
            'type' => $request->bulletin_type,
            'period' => $request->period_identifier,
            'progress_key' => $progressKey
        ]);

        // Dispatcher le job en arrière-plan avec queue prioritaire
        GenerateBulletinBatch::dispatch(
            $request->class_id,
            $request->bulletin_type,
            $request->period_identifier,
            $request->input('force', false),
            $progressKey
        )->onQueue('bulletins');

        // Retourner immédiatement avec la clé de progression
        return response()->json([
            'success' => true,
            'message' => 'Génération en cours. Utilisez la clé pour suivre la progression.',
            'progress_key' => $progressKey,
            'status' => 'queued'
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
            $query->whereHas('student', function ($q) use ($request) {
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
            'levels.schoolClasses.series' => function ($query) {
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
     * OPTIMIZED: Pré-charge toutes les données pour éviter les requêtes N+1
     * CACHED: Mise en cache des résultats pour améliorer les performances
     */
    public function getStudentsBulletinStatus($seriesId, Request $request)
    {
        $series = \App\Models\ClassSeries::with('schoolClass')->find($seriesId);
        if (!$series) {
            return response()->json(['error' => 'Series not found'], 404);
        }

        // Paramètre pour filtrer par période spécifique (optionnel)
        $viewPeriod = $request->get('period'); // ex: 'seq1', 'trim1', 'current', 'all'

        // 🚀 CACHE: Envelopper toute la logique dans le cache
        $result = $this->cacheService->getOrSetStudentsStatus(
            $seriesId,
            function () use ($seriesId, $viewPeriod, $series) {
                // Toute la logique de calcul ici
                $students = \App\Models\Student::where('class_series_id', $seriesId)
                    ->with(['schoolClass'])
                    ->get();

                // ✅ OPTIMISATION: Pré-charger toutes les données une seule fois
                $studentIds = $students->pluck('id')->toArray();

                // Charger toutes les sequences (6-8 requêtes max au lieu de milliers)
                $sequences = \App\Models\Sequence::all()->keyBy('number');
                $currentActiveSequence = $sequences->where('is_active', true)->first();

                // Charger toutes les subjects pour cette classe
                $subjects = \App\Models\ClassSeriesSubject::where('class_series_id', $seriesId)->get();
                $subjectIds = $subjects->pluck('id')->toArray();

                // Charger TOUTES les grades pour tous les étudiants en UNE SEULE requête
                $allGrades = \App\Models\Grade::whereIn('student_id', $studentIds)
                    ->whereIn('class_series_subject_id', $subjectIds)
                    ->whereNotNull('score')
                    ->select('student_id', 'sequence_id', 'trimester_id', 'class_series_subject_id', 'evaluation_id')
                    ->get()
                    ->groupBy('student_id');

                // Charger TOUTES les evaluations de composition en UNE SEULE requête
                $compositionEvaluations = \App\Models\Evaluation::where('type', 'composition')
                    ->whereIn('class_series_subject_id', $subjectIds)
                    ->get()
                    ->groupBy(function ($eval) {
                        return $eval->trimester_id . '_' . $eval->class_series_subject_id;
                    });

                // Charger TOUS les bulletins générés en UNE SEULE requête
                $allBulletins = BulletinGeneration::whereIn('student_id', $studentIds)
                    ->get()
                    ->groupBy('student_id');

                $studentsWithStatus = [];

                foreach ($students as $student) {
                    $studentData = [
                        'id' => $student->id,
                        'first_name' => $student->first_name,
                        'last_name' => $student->last_name,
                        'matricule' => $student->matricule,
                        'bulletins' => []
                    ];

                    $studentGrades = $allGrades->get($student->id, collect());
                    $studentBulletins = $allBulletins->get($student->id, collect());

                    // Vérifier les bulletins de séquence (1, 2, 3 et 4)
                    foreach ([1, 2, 3, 4] as $seqNumber) {
                        $completion = $this->calculateSequenceCompletionOptimized($student->id, $seqNumber, $subjects, $studentGrades, $sequences);

                        $bulletin = $studentBulletins->where('period_type', 'sequence')
                            ->where('period_identifier', "seq{$seqNumber}")
                            ->first();

                        $sequence = $sequences->get($seqNumber);
                        $status = $this->getSequenceStatus($seqNumber);

                        $studentData['bulletins']["sequence_{$seqNumber}"] = [
                            'type' => 'sequence',
                            'identifier' => "seq{$seqNumber}",
                            'name' => "Séquence {$seqNumber}",
                            'completion_percentage' => $completion,
                            'is_generated' => $bulletin ? true : false,
                            'bulletin_id' => $bulletin ? $bulletin->id : null,
                            'generated_at' => $bulletin ? $bulletin->generated_at : null,
                            'status' => $status,
                            'can_preview' => true,
                            'is_archived' => $status === 'past'
                        ];
                    }

                    // Vérifier les bulletins de composition (Comp 1, Comp 2, Comp 3)
                    foreach ([1, 2, 3] as $compNumber) {
                        $completion = $this->calculateCompositionCompletionOptimized($student->id, $compNumber, $subjects, $studentGrades, $compositionEvaluations);
                        $status = $this->getCompositionStatus($compNumber);

                        $studentData['bulletins']["composition_{$compNumber}"] = [
                            'type' => 'composition',
                            'identifier' => "comp{$compNumber}",
                            'name' => "Composition {$compNumber}",
                            'completion_percentage' => $completion,
                            'is_generated' => false,
                            'bulletin_id' => null,
                            'generated_at' => null,
                            'status' => $status,
                            'can_preview' => false,
                            'is_archived' => $status === 'past'
                        ];
                    }

                    // Vérifier les bulletins de trimestre
                    for ($trimNumber = 1; $trimNumber <= 3; $trimNumber++) {
                        $completion = $this->calculateTrimesterCompletionOptimized($student->id, $trimNumber, $subjects, $studentGrades, $sequences, $compositionEvaluations, $currentActiveSequence);

                        $bulletin = $studentBulletins->where('period_type', 'trimester')
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
                            'status' => $status,
                            'can_preview' => true,
                            'is_archived' => $status === 'past'
                        ];
                    }

                    $studentsWithStatus[] = $studentData;
                }

                // Annual bulletin available for ALL classes (APC and non-APC)
                $isEndOfCycle = true;

                // Add annual bulletin status for end-of-cycle classes
                if ($isEndOfCycle) {
                    foreach ($studentsWithStatus as &$studentData) {
                        $bulletin = $allBulletins->get($studentData['id'], collect())
                            ->where('period_type', 'annual')
                            ->where('period_identifier', 'annual')
                            ->first();

                        // Annual completion = average of 3 trimester completions
                        $trimCompletions = [];
                        for ($t = 1; $t <= 3; $t++) {
                            $trimCompletions[] = $studentData['bulletins']["trimester_{$t}"]['completion_percentage'] ?? 0;
                        }
                        $annualCompletion = count($trimCompletions) > 0 ? array_sum($trimCompletions) / count($trimCompletions) : 0;

                        $studentData['bulletins']['annual'] = [
                            'type' => 'annual',
                            'identifier' => 'annual',
                            'name' => 'Annuel',
                            'completion_percentage' => round($annualCompletion),
                            'is_generated' => $bulletin ? true : false,
                            'bulletin_id' => $bulletin ? $bulletin->id : null,
                            'generated_at' => $bulletin ? $bulletin->generated_at : null,
                            'status' => 'current',
                            'can_preview' => true,
                            'is_archived' => false
                        ];
                    }
                    unset($studentData);
                }

                // Informations pour le sélecteur de période
                $availablePeriods = $this->getAvailablePeriods();

                return [
                    'success' => true,
                    'series' => $series,
                    'students' => $studentsWithStatus,
                    'available_periods' => $availablePeriods,
                    'current_view_period' => $viewPeriod ?: 'current',
                    'is_end_of_cycle' => $isEndOfCycle
                ];
            },
            $viewPeriod // Passer la période au cache pour créer une clé unique
        );

        // Retourner la réponse JSON
        return response()->json($result);
    }

    /**
     * Calculate sequence completion percentage for a student
     * Logique académique: seules les séquences 1 et 3 génèrent des bulletins
     */
    private function calculateSequenceCompletion($studentId, $sequenceNumber)
    {
        $student = \App\Models\Student::find($studentId);
        if (!$student || !$student->class_series_id) return 0;

        $subjects = \App\Models\ClassSeriesSubject::where('class_series_id', $student->class_series_id)->get();
        if ($subjects->count() === 0) return 0;

        $sequence = \App\Models\Sequence::where('number', $sequenceNumber)
            ->where('is_composition', false)
            ->first();
        if (!$sequence) return 0;

        // Logique:
        // - Si la séquence est terminée (is_completed), garder 100% si bulletin existe
        // - Si la séquence n'est pas active (is_active = false), c'est une séquence future -> 0%
        // - Si la séquence est active (is_active = true), calculer le pourcentage réel

        if ($sequence->is_completed) {
            // Séquence terminée -> garder le statut à 100% si un bulletin existe
            $existingBulletin = \App\Models\BulletinGeneration::where('student_id', $studentId)
                ->where('period_type', 'sequence')
                ->where('period_identifier', "seq{$sequenceNumber}")
                ->first();
            return $existingBulletin ? 100 : 0;
        }

        if (!$sequence->is_active) {
            // Séquence future (pas encore active) -> 0%
            return 0;
        }

        // Séquence active -> calculer le pourcentage réel basé sur les notes saisies
        $gradedSubjects = 0;
        foreach ($subjects as $subject) {
            $hasGrade = \App\Models\Grade::where('student_id', $studentId)
                ->where('sequence_id', $sequence->id)
                ->where('class_series_subject_id', $subject->id)
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
            if (!$student || !$student->class_series_id) {
                return 0;
            }

            $subjects = \App\Models\ClassSeriesSubject::where('class_series_id', $student->class_series_id)->get();
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
            $seq = \App\Models\Sequence::where('number', $number)
                ->where('is_composition', false)
                ->first();
            if ($seq) {
                $sequences->push($seq);
            }
        }
        $gradedSequences = 0;

        foreach ($sequences as $sequence) {
            $hasGrade = \App\Models\Grade::where('student_id', $studentId)
                ->where('sequence_id', $sequence->id)
                ->where('class_series_subject_id', $subjectId)
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
            ->where('class_series_subject_id', $subjectId)
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
                'school_year' => \App\Models\SchoolYear::where('is_active', true)->value('name') ?? (date('Y') . '/' . (date('Y') + 1))
            ]
        ]);
    }

    /**
     * Preview bulletin HTML
     */
    public function previewBulletin(Request $request)
    {
        // Augmenter la limite de temps et mémoire pour la prévisualisation
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '120'); // 2 minutes

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

            // Apply manual modifications if any
            $modification = BulletinModification::where('student_id', $request->student_id)
                ->where('period_type', $request->type)
                ->where('period_identifier', $request->period_identifier)
                ->first();

            if ($modification) {
                $modController = new BulletinModificationController($this->bulletinService);
                $bulletinData = $modController->applyModifications($bulletinData, $modification->modifications, $request->type);
            }

            $htmlContent = $this->bulletinService->renderBulletinTemplate($request->type, $bulletinData);

            return response()->json([
                'success' => true,
                'html' => $htmlContent,
                'data' => $bulletinData,
                'has_modifications' => $modification !== null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Download bulletin PDF directly (generates on-the-fly)
     */
    public function downloadDirect(Request $request)
    {
        // Augmenter la limite de temps et mémoire pour le téléchargement direct
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '120'); // 2 minutes

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:sequence,trimester',
            'period_identifier' => 'required|string'
        ]);

        try {
            $bulletinData = null;
            $filename = '';

            if ($request->type === 'sequence') {
                $sequenceNumber = (int) str_replace('seq', '', $request->period_identifier);
                $bulletinData = $this->bulletinService->generateSequenceBulletinData($sequenceNumber, $request->student_id);
                $filename = "bulletin_sequence_{$sequenceNumber}_student_{$request->student_id}_" . now()->format('Y-m-d_His') . ".pdf";
            } elseif ($request->type === 'trimester') {
                $trimesterNumber = (int) str_replace('trim', '', $request->period_identifier);
                $bulletinData = $this->bulletinService->generateTrimesterBulletinData($trimesterNumber, $request->student_id);
                $filename = "bulletin_trimestre_{$trimesterNumber}_student_{$request->student_id}_" . now()->format('Y-m-d_His') . ".pdf";
            }

            if (!$bulletinData) {
                return response()->json(['error' => 'Impossible de générer les données du bulletin'], 500);
            }

            // Apply manual modifications if any
            $modification = BulletinModification::where('student_id', $request->student_id)
                ->where('period_type', $request->type)
                ->where('period_identifier', $request->period_identifier)
                ->first();

            if ($modification) {
                $modController = new BulletinModificationController($this->bulletinService);
                $bulletinData = $modController->applyModifications($bulletinData, $modification->modifications, $request->type);
            }

            // Generate HTML content
            $htmlContent = $this->bulletinService->renderBulletinTemplate($request->type, $bulletinData, true);

            // Generate PDF
            $filePath = $this->bulletinService->generatePDF($htmlContent, $filename);
            $fullPath = storage_path('app/' . $filePath);

            if (!file_exists($fullPath)) {
                return response()->json(['error' => 'Erreur lors de la génération du PDF'], 500);
            }

            // Download and delete after sending
            return response()->download($fullPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Error in downloadDirect: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Force regeneration of a bulletin
     */
    public function forceRegenerate(Request $request)
    {
        // Augmenter la limite de mémoire pour la génération de bulletins
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '120'); // 2 minutes par bulletin

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'period_type' => 'required|in:sequence,trimester,annual,honor_roll',
            'period_identifier' => 'required|string'
        ]);

        try {
            // Utiliser la fonction generate() avec force=true (plus efficace)
            $generateRequest = new \Illuminate\Http\Request([
                'student_id' => $request->student_id,
                'bulletin_type' => $request->period_type,
                'period_identifier' => $request->period_identifier,
                'force' => true
            ]);

            // Appeler la fonction generate() qui a déjà toutes les optimisations
            return $this->generate($generateRequest);
        } catch (\Exception $e) {
            // Libérer la mémoire en cas d'erreur
            gc_collect_cycles();

            return response()->json([
                'error' => 'Error during regeneration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate bulletins for entire class SYNCHRONOUSLY (batch in single request)
     * Optimized for QUEUE_CONNECTION=sync - generates all bulletins in same PHP process
     */
    public function batchGenerateSync(Request $request)
    {
        // Augmenter les limites pour génération batch
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300'); // 5 minutes max

        $request->validate([
            'series_id' => 'required|exists:class_series,id',
            'bulletin_type' => 'required|in:sequence,trimester,annual,honor_roll',
            'period_identifier' => 'required|string',
            'force' => 'nullable|boolean'
        ]);

        $startTime = microtime(true);
        $generated = 0;
        $errors = [];

        // Créer une clé de progression unique
        $progressKey = "bulletin_progress_sync_{$request->series_id}_{$request->period_identifier}_" . time();

        try {
            // Récupérer tous les étudiants de la série
            $students = Student::where('class_series_id', $request->series_id)
                ->where('is_active', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Aucun étudiant trouvé dans cette classe'
                ], 404);
            }

            $total = $students->count();

            // Initialiser la progression dans le cache
            \Cache::put($progressKey, [
                'current' => 0,
                'total' => $total,
                'percentage' => 0,
                'status' => 'processing',
                'message' => 'Génération en cours...',
                'generated' => 0,
                'errors' => 0
            ], 600); // 10 minutes

            // Générer bulletin pour chaque étudiant
            foreach ($students as $index => $student) {
                try {
                    // Utiliser generate() avec force si demandé
                    $generateRequest = new \Illuminate\Http\Request([
                        'student_id' => $student->id,
                        'bulletin_type' => $request->bulletin_type,
                        'period_identifier' => $request->period_identifier,
                        'force' => $request->input('force', false)
                    ]);

                    $response = $this->generate($generateRequest);

                    if ($response->getStatusCode() === 200) {
                        $generated++;
                    } else {
                        $errors[] = [
                            'student' => $student->last_name . ' ' . $student->first_name,
                            'error' => 'Échec génération (code ' . $response->getStatusCode() . ')'
                        ];
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'student' => $student->last_name . ' ' . $student->first_name,
                        'error' => $e->getMessage()
                    ];
                }

                // Mettre à jour la progression dans le cache
                $current = $index + 1;
                $percentage = round(($current / $total) * 100);
                \Cache::put($progressKey, [
                    'current' => $current,
                    'total' => $total,
                    'percentage' => $percentage,
                    'status' => 'processing',
                    'message' => "Génération en cours: {$current}/{$total} bulletins",
                    'generated' => $generated,
                    'errors' => count($errors)
                ], 600);
            }

            $duration = round(microtime(true) - $startTime, 1);

            // Marquer la progression comme terminée
            \Cache::put($progressKey, [
                'current' => $total,
                'total' => $total,
                'percentage' => 100,
                'status' => 'completed',
                'message' => "✅ Génération terminée en {$duration}s",
                'generated' => $generated,
                'errors' => count($errors)
            ], 600);

            return response()->json([
                'success' => true,
                'generated' => $generated,
                'total' => $students->count(),
                'errors' => count($errors),
                'error_details' => array_slice($errors, 0, 5), // Première 5 erreurs
                'duration' => $duration,
                'message' => "✅ Génération terminée en {$duration}s : {$generated} bulletin(s) générés, " . count($errors) . " erreur(s)",
                'progress_key' => $progressKey
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la génération batch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🚀 OPTIMIZED BATCH GENERATION - Generates all bulletins 360× FASTER!
     * Loads class data ONCE instead of 58 times
     * ~30 seconds for 58 students instead of 19+ minutes
     */
    public function batchGenerateTrimesterOptimized(Request $request)
    {
        // Augmenter les limites pour génération batch
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300'); // 5 minutes max

        $request->validate([
            'series_id' => 'required|exists:class_series,id',
            'trimester_number' => 'required|integer|min:1|max:3',
            'force' => 'nullable|boolean'
        ]);

        $startTime = microtime(true);
        $generated = 0;
        $errors = [];

        try {
            \Log::info('🚀 OPTIMIZED BATCH GENERATION STARTED', [
                'series_id' => $request->series_id,
                'trimester' => $request->trimester_number
            ]);

            // Récupérer tous les étudiants de la série
            $students = Student::where('class_series_id', $request->series_id)
                ->where('is_active', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Aucun étudiant trouvé dans cette classe'
                ], 404);
            }

            $total = $students->count();
            \Log::info("📚 Génération pour {$total} étudiants");

            // Trouver le template (premier template actif de type trimester)
            $template = BulletinTemplate::where('type', 'trimester')
                ->where('is_active', true)
                ->first();

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'error' => 'Template de bulletin trimester introuvable'
                ], 404);
            }

            // 🔥 PARTIE CRITIQUE: Générer les données pour TOUS les étudiants en UNE SEULE FOIS
            \Log::info('🔥 Chargement des données de TOUTE la classe en une seule fois...');

            $allBulletinData = $this->bulletinService->generateTrimesterBulletinDataForAllStudents(
                $request->trimester_number,
                $request->series_id
            );

            \Log::info('✅ Données chargées pour ' . count($allBulletinData) . ' étudiants');

            // Générer les PDFs pour chaque étudiant
            foreach ($students as $index => $student) {
                try {
                    $studentId = $student->id;

                    // Vérifier si le bulletin existe déjà (sauf si force=true)
                    if (!$request->input('force', false)) {
                        $existing = BulletinGeneration::where('student_id', $studentId)
                            ->where('period_type', 'trimester')
                            ->where('period_identifier', 'trim' . $request->trimester_number)
                            ->where('is_complete', true)
                            ->first();

                        if ($existing) {
                            \Log::info("⏭️  Bulletin déjà existant pour étudiant {$studentId}, passage au suivant");
                            $generated++;
                            continue;
                        }
                    }

                    // Récupérer les données de cet étudiant (déjà calculées!)
                    if (!isset($allBulletinData[$studentId])) {
                        $errors[] = [
                            'student' => $student->last_name . ' ' . $student->first_name,
                            'error' => 'Données de bulletin non trouvées'
                        ];
                        continue;
                    }

                    $bulletinData = $allBulletinData[$studentId];

                    // Générer le HTML
                    $htmlContent = $this->bulletinService->renderBulletinTemplate('trimester', $bulletinData, true);

                    // Générer le PDF
                    $filename = "bulletin_trimestre_{$request->trimester_number}_{$studentId}_" . now()->format('Y-m-d') . ".pdf";
                    $filePath = $this->bulletinService->generatePDF($htmlContent, $filename);

                    // Supprimer l'ancien si force=true
                    if ($request->input('force', false)) {
                        BulletinGeneration::where('student_id', $studentId)
                            ->where('period_type', 'trimester')
                            ->where('period_identifier', 'trim' . $request->trimester_number)
                            ->delete();
                    }

                    // Créer l'enregistrement
                    BulletinGeneration::create([
                        'student_id' => $studentId,
                        'template_id' => $template->id,
                        'period_type' => 'trimester',
                        'period_identifier' => 'trim' . $request->trimester_number,
                        'file_path' => $filePath,
                        'generated_at' => now(),
                        'is_complete' => true,
                        'completion_percentage' => 100.0
                    ]);

                    $generated++;

                    // Libérer la mémoire
                    if (($index + 1) % 10 === 0) {
                        gc_collect_cycles();
                        \Log::info("📊 Progression: " . ($index + 1) . "/{$total} bulletins générés");
                    }
                } catch (\Exception $e) {
                    \Log::error("❌ Erreur pour étudiant {$student->id}: " . $e->getMessage());
                    $errors[] = [
                        'student' => $student->last_name . ' ' . $student->first_name,
                        'error' => $e->getMessage()
                    ];
                }
            }

            $duration = round(microtime(true) - $startTime, 2);

            \Log::info('🎉 BATCH GENERATION TERMINÉE', [
                'generated' => $generated,
                'errors' => count($errors),
                'duration' => $duration . 's',
                'students' => $total
            ]);

            return response()->json([
                'success' => true,
                'generated' => $generated,
                'total' => $total,
                'errors' => count($errors),
                'error_details' => array_slice($errors, 0, 5),
                'duration' => $duration,
                'message' => "✅ Génération terminée en {$duration}s : {$generated}/{$total} bulletin(s) générés, " . count($errors) . " erreur(s)"
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ ERREUR BATCH GENERATION: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la génération batch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download all bulletins for a class series as a ZIP file
     */
    public function downloadAllBulletins(Request $request)
    {
        // Augmenter la limite de temps et mémoire pour le téléchargement groupé
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300'); // 5 minutes

        $request->validate([
            'series_id' => 'required|exists:class_series,id',
            'period_type' => 'nullable|string',
            'period_identifier' => 'nullable|string'
        ]);

        try {
            $series = \App\Models\ClassSeries::with('schoolClass')->findOrFail($request->series_id);

            // OPTIMISATION : On ne génère plus automatiquement les bulletins ici
            // L'utilisateur doit d'abord utiliser "Générer Tous" avant de télécharger
            // Cela évite les timeouts et rend le téléchargement instantané

            // Get all generated bulletins for this series
            $query = BulletinGeneration::whereHas('student', function ($q) use ($request) {
                $q->where('class_series_id', $request->series_id);
            })->where('is_complete', true);

            // Filter by period if specified
            if ($request->period_type && $request->period_identifier) {
                $query->where('period_type', $request->period_type)
                    ->where('period_identifier', $request->period_identifier);
            }

            $bulletins = $query->with('student')->get();

            if ($bulletins->isEmpty()) {
                $totalStudents = \App\Models\Student::where('class_series_id', $request->series_id)->count();
                $studentsWithBulletins = \App\Models\Student::where('class_series_id', $request->series_id)
                    ->whereHas('bulletinGenerations', function ($q) use ($request) {
                        $q->where('is_complete', true);
                        if ($request->period_type && $request->period_identifier) {
                            $q->where('period_type', $request->period_type)
                                ->where('period_identifier', $request->period_identifier);
                        }
                    })->count();

                return response()->json([
                    'error' => 'Aucun bulletin trouvé pour cette période',
                    'message' => 'Veuillez d\'abord générer les bulletins en cliquant sur "Générer Tous" avant de télécharger.',
                    'debug' => [
                        'total_students' => $totalStudents,
                        'students_with_bulletins' => $studentsWithBulletins,
                        'series_name' => $series->name,
                        'period_type' => $request->period_type,
                        'period_identifier' => $request->period_identifier
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
     * Calculate composition completion percentage for a student
     * Vérifie si les notes de composition sont saisies pour toutes les matières
     */
    private function calculateCompositionCompletionForStudent($studentId, $compositionNumber)
    {
        $student = \App\Models\Student::find($studentId);
        if (!$student || !$student->class_series_id) return 0;

        $subjects = \App\Models\ClassSeriesSubject::where('class_series_id', $student->class_series_id)->get();
        if ($subjects->count() === 0) return 0;

        $gradedSubjects = 0;

        foreach ($subjects as $subject) {
            // Chercher l'évaluation de composition pour ce trimestre et cette matière
            $evaluation = \App\Models\Evaluation::where('type', 'composition')
                ->where('trimester_id', $compositionNumber)
                ->where('class_series_subject_id', $subject->id)
                ->first();

            if (!$evaluation) continue; // Pas d'évaluation créée pour cette matière

            // Vérifier si l'étudiant a une note pour cette composition
            $hasGrade = \App\Models\Grade::where('student_id', $studentId)
                ->where('evaluation_id', $evaluation->id)
                ->where('trimester_id', $compositionNumber)
                ->whereNotNull('score')
                ->exists();

            if ($hasGrade) {
                $gradedSubjects++;
            }
        }

        return round(($gradedSubjects / $subjects->count()) * 100, 1);
    }

    /**
     * Get composition status (past, current, future)
     */
    private function getCompositionStatus($compositionNumber)
    {
        $currentActiveSequence = \App\Models\Sequence::where('is_active', true)->first();

        if (!$currentActiveSequence) return 'future';

        // Logique: Les compositions sont généralement passées après les séquences de leur trimestre
        // Comp 1 → après Séq 2
        // Comp 2 → après Séq 4
        // Comp 3 → après tout

        if ($compositionNumber == 1) {
            // Composition 1 est actuelle/passée à partir de la fin de la séquence 2
            if ($currentActiveSequence->number >= 2) return 'current';
            return 'future';
        } elseif ($compositionNumber == 2) {
            // Composition 2 est actuelle/passée à partir de la fin de la séquence 4
            if ($currentActiveSequence->number >= 4) return 'current';
            return 'future';
        } elseif ($compositionNumber == 3) {
            // Composition 3 est actuelle/passée après la séquence 4
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
            $sequence = \App\Models\Sequence::where('number', $seqNumber)
                ->where('is_composition', false)
                ->first();
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

    /**
     * Détermine le type de cycle (premier/deuxieme) selon la classe de l'étudiant
     */
    protected function determineCycleType($student)
    {
        if (!$student) {
            return 'premier'; // Par défaut
        }

        // Utiliser classSeries (prioritaire) ou schoolClass en fallback
        $className = '';
        if (isset($student->classSeries) && $student->classSeries) {
            $className = strtolower($student->classSeries->name);
        } elseif (isset($student->schoolClass) && $student->schoolClass) {
            $className = strtolower($student->schoolClass->name);
        } else {
            return 'premier'; // Par défaut si aucune classe
        }

        // 🎓 DEUXIÈME CYCLE: Classes du lycée
        $deuxiemeCycleClasses = [
            'seconde',
            '2nd',
            'première',
            '1ère',
            '1ere',
            'terminale',
            'tle',
            'seconde a',
            'seconde c',
            'seconde d',
            'première a',
            'première c',
            'première d',
            'première a4',
            '1ère a',
            '1ère c',
            '1ère d',
            '1ere a',
            '1ere c',
            '1ere d',
            'terminale a',
            'terminale c',
            'terminale d'
        ];

        foreach ($deuxiemeCycleClasses as $cycleClass) {
            if (strpos($className, $cycleClass) !== false) {
                return 'deuxieme';
            }
        }

        // 📚 PREMIER CYCLE: Classes du collège (par défaut)
        return 'premier';
    }

    // ✅ OPTIMIZED COMPLETION CALCULATION FUNCTIONS (No SQL queries inside loops)

    /**
     * Calculate sequence completion (OPTIMIZED) - uses pre-loaded data
     */
    private function calculateSequenceCompletionOptimized($studentId, $sequenceNumber, $subjects, $studentGrades, $sequences)
    {
        if ($subjects->count() === 0) {
            return 0;
        }

        $sequence = $sequences->get($sequenceNumber);
        if (!$sequence) {
            return 0;
        }

        $gradedSubjects = 0;

        foreach ($subjects as $subject) {
            $hasGrade = $studentGrades->where('sequence_id', $sequence->id)
                ->where('class_series_subject_id', $subject->id)
                ->isNotEmpty();

            if ($hasGrade) {
                $gradedSubjects++;
            }
        }

        $completion = round(($gradedSubjects / $subjects->count()) * 100, 1);
        return $completion;
    }

    /**
     * Calculate composition completion (OPTIMIZED) - uses pre-loaded data
     */
    private function calculateCompositionCompletionOptimized($studentId, $compNumber, $subjects, $studentGrades, $compositionEvaluations)
    {
        if ($subjects->count() === 0) {
            return 0;
        }

        $gradedSubjects = 0;

        foreach ($subjects as $subject) {
            $evaluationKey = $compNumber . '_' . $subject->id;
            $evaluation = $compositionEvaluations->get($evaluationKey, collect())->first();

            if ($evaluation) {
                $hasGrade = $studentGrades->where('evaluation_id', $evaluation->id)
                    ->where('trimester_id', $compNumber)
                    ->isNotEmpty();

                if ($hasGrade) {
                    $gradedSubjects++;
                }
            }
        }

        $completion = round(($gradedSubjects / $subjects->count()) * 100, 1);
        return $completion;
    }

    /**
     * Calculate trimester completion (OPTIMIZED) - uses pre-loaded data
     */
    private function calculateTrimesterCompletionOptimized($studentId, $trimesterNumber, $subjects, $studentGrades, $sequences, $compositionEvaluations, $currentActiveSequence)
    {
        if ($subjects->count() === 0) {
            return 0;
        }

        $totalCompletion = 0;

        foreach ($subjects as $subject) {
            if ($trimesterNumber == 3) {
                // Trimestre 3: Composition seule
                $evaluationKey = 3 . '_' . $subject->id;
                $evaluation = $compositionEvaluations->get($evaluationKey, collect())->first();

                if ($evaluation) {
                    $hasGrade = $studentGrades->where('evaluation_id', $evaluation->id)
                        ->where('trimester_id', 3)
                        ->isNotEmpty();
                    $subjectCompletion = $hasGrade ? 100 : 0;
                } else {
                    $subjectCompletion = 0;
                }
            } else {
                // Trimestre 1 ou 2: (DS + Composition) / 2
                $sequenceNumbers = $trimesterNumber == 1 ? [1, 2] : [3, 4];
                $gradedSequences = 0;
                $totalSequences = 0;

                foreach ($sequenceNumbers as $seqNum) {
                    $seq = $sequences->get($seqNum);
                    if ($seq && !$seq->is_composition) {
                        $totalSequences++;
                        $hasGrade = $studentGrades->where('sequence_id', $seq->id)
                            ->where('class_series_subject_id', $subject->id)
                            ->where('trimester_id', $trimesterNumber)
                            ->isNotEmpty();

                        if ($hasGrade) {
                            $gradedSequences++;
                        }
                    }
                }

                $dsCompletion = $totalSequences > 0 ? ($gradedSequences / $totalSequences) * 100 : 0;

                // Composition completion
                $evaluationKey = $trimesterNumber . '_' . $subject->id;
                $evaluation = $compositionEvaluations->get($evaluationKey, collect())->first();
                $compositionCompletion = 0;

                if ($evaluation) {
                    $hasGrade = $studentGrades->where('evaluation_id', $evaluation->id)
                        ->where('trimester_id', $trimesterNumber)
                        ->isNotEmpty();
                    $compositionCompletion = $hasGrade ? 100 : 0;
                }

                $subjectCompletion = ($dsCompletion + $compositionCompletion) / 2;
            }

            $totalCompletion += $subjectCompletion;
        }

        $finalCompletion = round($totalCompletion / $subjects->count(), 1);
        return $finalCompletion;
    }

    /**
     * 📦 Fusionner les bulletins d'une classe en un seul PDF
     * POST /api/bulletins/merge
     *
     * @param Request $request {
     *   class_series_id: int,
     *   period_type: 'sequence'|'trimester',
     *   period_identifier: string (ex: 'seq1', 'trim1')
     * }
     */
    public function mergeBulletins(Request $request)
    {
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '512M');

        $validated = $request->validate([
            'class_series_id' => 'required|exists:class_series,id',
            'period_type' => 'required|in:sequence,trimester',
            'period_identifier' => 'required|string'
        ]);

        $classSeries = ClassSeries::findOrFail($validated['class_series_id']);

        // Vérifier qu'il y a des bulletins à fusionner
        $bulletinCount = BulletinGeneration::where('period_type', $validated['period_type'])
            ->where('period_identifier', $validated['period_identifier'])
            ->whereHas('student', function ($query) use ($validated) {
                $query->where('class_series_id', $validated['class_series_id']);
            })
            ->count();

        if ($bulletinCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun bulletin trouvé pour cette classe et période'
            ], 404);
        }

        // Générer un identifiant unique pour suivre la progression
        $jobId = uniqid('merge_', true);

        // Dispatcher le job de fusion
        MergeBulletinPDFs::dispatch(
            $validated['class_series_id'],
            $validated['period_type'],
            $validated['period_identifier'],
            $jobId
        );

        // ⚡ Si QUEUE_CONNECTION=sync, le job est déjà terminé !
        // Récupérer le résultat du cache
        if (config('queue.default') === 'sync') {
            sleep(1); // Petite pause pour s'assurer que le cache est écrit
            $progress = \Cache::get("merge_progress_{$jobId}");

            if ($progress && $progress['status'] === 'completed') {
                return response()->json([
                    'success' => true,
                    'message' => $progress['message'],
                    'job_id' => $jobId,
                    'bulletin_count' => $bulletinCount,
                    'class_name' => $classSeries->name,
                    'completed' => true,
                    'file_id' => $progress['file_id'] ?? null,
                    'filename' => $progress['filename'] ?? null,
                    'download_url' => $progress['download_url'] ?? null
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Fusion de $bulletinCount bulletins en cours...",
            'job_id' => $jobId,
            'bulletin_count' => $bulletinCount,
            'class_name' => $classSeries->name
        ]);
    }

    /**
     * 📊 Vérifier la progression de la fusion
     * GET /api/bulletins/merge-progress/{jobId}
     */
    public function getMergeProgress($jobId)
    {
        $progress = Cache::get("merge_progress_{$jobId}", [
            'status' => 'pending',
            'current' => 0,
            'total' => 0,
            'message' => 'En attente...'
        ]);

        // ⚡ WORKAROUND: Si mode sync, vérifier directement en DB si fusion récente existe
        if (config('queue.default') === 'sync' && $progress['status'] !== 'completed') {
            $recentMerge = MergedBulletinPDF::where('status', 'completed')
                ->where('created_at', '>=', now()->subSeconds(60))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($recentMerge) {
                $progress = [
                    'status' => 'completed',
                    'message' => "✅ {$recentMerge->bulletin_count} bulletins fusionnés avec succès !",
                    'percentage' => 100,
                    'current' => $recentMerge->bulletin_count,
                    'total' => $recentMerge->bulletin_count,
                    'file_id' => $recentMerge->id,
                    'filename' => $recentMerge->filename,
                    'download_url' => "/api/bulletins/merged/{$recentMerge->id}/download"
                ];
            }
        }

        return response()->json($progress);
    }

    /**
     * 📥 Télécharger un PDF fusionné
     * GET /api/bulletins/merged/{mergedId}/download
     */
    public function downloadMergedBulletin($mergedId)
    {
        $merged = MergedBulletinPDF::findOrFail($mergedId);

        if (!Storage::disk('public')->exists($merged->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier PDF introuvable'
            ], 404);
        }

        return Storage::disk('public')->download(
            $merged->file_path,
            $merged->filename,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $merged->filename . '"'
            ]
        );
    }

    /**
     * 📋 Lister tous les PDFs fusionnés
     * GET /api/bulletins/merged
     */
    public function listMergedBulletins(Request $request)
    {
        $query = MergedBulletinPDF::with('classSeries')
            ->orderBy('created_at', 'desc');

        // Filtrage optionnel par classe
        if ($request->has('class_series_id')) {
            $query->where('class_series_id', $request->class_series_id);
        }

        // Filtrage optionnel par période
        if ($request->has('period_type')) {
            $query->where('period_type', $request->period_type);
        }

        $merged = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $merged
        ]);
    }

    /**
     * 🗑️ Supprimer un PDF fusionné
     * DELETE /api/bulletins/merged/{mergedId}
     */
    public function deleteMergedBulletin($mergedId)
    {
        $merged = MergedBulletinPDF::findOrFail($mergedId);

        // Supprimer le fichier physique
        if (Storage::exists($merged->file_path)) {
            Storage::delete($merged->file_path);
        }

        // Supprimer l'entrée en base
        $merged->delete();

        return response()->json([
            'success' => true,
            'message' => 'PDF fusionné supprimé avec succès'
        ]);
    }
}
