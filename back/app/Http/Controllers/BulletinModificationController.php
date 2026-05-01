<?php

namespace App\Http\Controllers;

use App\Models\BulletinModification;
use App\Models\Student;
use App\Services\BulletinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BulletinModificationController extends Controller
{
    protected $bulletinService;

    public function __construct(BulletinService $bulletinService)
    {
        $this->bulletinService = $bulletinService;
    }

    /**
     * Get editable bulletin data for a student/period
     */
    public function getBulletinData(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:sequence,trimester,annual',
            'period_identifier' => 'required|string',
        ]);

        try {
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '120');

            $bulletinData = $this->generateBulletinData(
                $request->type,
                $request->period_identifier,
                $request->student_id
            );

            if (!$bulletinData) {
                return response()->json(['error' => 'Impossible de generer les donnees du bulletin'], 500);
            }

            // Get existing modifications
            $existing = BulletinModification::where('student_id', $request->student_id)
                ->where('period_type', $request->type)
                ->where('period_identifier', $request->period_identifier)
                ->first();

            // Build editable structure
            $editableData = $this->buildEditableData($bulletinData, $request->type);

            return response()->json([
                'success' => true,
                'data' => $editableData,
                'existing_modifications' => $existing ? $existing->modifications : null,
                'modification_reason' => $existing ? $existing->reason : null,
                'last_modified' => $existing ? $existing->updated_at : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur chargement donnees bulletin: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Save bulletin modifications
     */
    public function saveModifications(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'period_type' => 'required|in:sequence,trimester,annual',
            'period_identifier' => 'required|string',
            'modifications' => 'required|array',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $modification = BulletinModification::updateOrCreate(
                [
                    'student_id' => $request->student_id,
                    'period_type' => $request->period_type,
                    'period_identifier' => $request->period_identifier,
                ],
                [
                    'modifications' => $request->modifications,
                    'reason' => $request->reason,
                    'modified_by' => Auth::id(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Modifications enregistrees avec succes',
                'modification' => $modification,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur sauvegarde modifications bulletin: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete modifications (reset to original)
     */
    public function deleteModifications(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'period_type' => 'required|in:sequence,trimester,annual',
            'period_identifier' => 'required|string',
        ]);

        $deleted = BulletinModification::where('student_id', $request->student_id)
            ->where('period_type', $request->period_type)
            ->where('period_identifier', $request->period_identifier)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted ? 'Modifications supprimees' : 'Aucune modification trouvee',
        ]);
    }

    /**
     * Preview bulletin with modifications applied
     */
    public function previewWithModifications(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:sequence,trimester,annual',
            'period_identifier' => 'required|string',
        ]);

        try {
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '120');

            $bulletinData = $this->generateBulletinData(
                $request->type,
                $request->period_identifier,
                $request->student_id
            );

            if (!$bulletinData) {
                return response()->json(['error' => 'Impossible de generer les donnees'], 500);
            }

            // Apply modifications
            $modification = BulletinModification::where('student_id', $request->student_id)
                ->where('period_type', $request->type)
                ->where('period_identifier', $request->period_identifier)
                ->first();

            if ($modification) {
                $bulletinData = $this->applyModifications($bulletinData, $modification->modifications, $request->type);
            }

            $htmlContent = $this->bulletinService->renderBulletinTemplate($request->type, $bulletinData);

            return response()->json([
                'success' => true,
                'html' => $htmlContent,
                'has_modifications' => $modification !== null,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur preview avec modifications: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Regenerate bulletin PDF with modifications applied
     */
    public function regenerateWithModifications(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:sequence,trimester,annual',
            'period_identifier' => 'required|string',
        ]);

        try {
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '120');

            $bulletinData = $this->generateBulletinData(
                $request->type,
                $request->period_identifier,
                $request->student_id
            );

            if (!$bulletinData) {
                return response()->json(['error' => 'Impossible de generer les donnees'], 500);
            }

            // Apply modifications
            $modification = BulletinModification::where('student_id', $request->student_id)
                ->where('period_type', $request->type)
                ->where('period_identifier', $request->period_identifier)
                ->first();

            if ($modification) {
                $bulletinData = $this->applyModifications($bulletinData, $modification->modifications, $request->type);
            }

            // Render HTML (PDF version)
            $htmlContent = $this->bulletinService->renderBulletinTemplate($request->type, $bulletinData, true);

            // Generate PDF
            $periodLabel = $request->type === 'sequence' ? 'sequence' : ($request->type === 'trimester' ? 'trimestre' : 'annuel');
            $periodNum = str_replace(['seq', 'trim', 'annual'], '', $request->period_identifier);
            $filename = "bulletin_{$periodLabel}_{$periodNum}_{$request->student_id}_" . now()->format('Y-m-d') . ".pdf";

            $filePath = $this->bulletinService->generatePDF($htmlContent, $filename);

            // Update bulletin_generations record
            $template = \App\Models\BulletinTemplate::where('type', $request->type)->where('is_active', true)->first();
            if (!$template) {
                $template = \App\Models\BulletinTemplate::firstOrCreate(
                    ['type' => $request->type, 'name' => 'CPBD Template'],
                    ['template_html' => 'Default', 'is_active' => true, 'description' => 'Template CPBD']
                );
            }

            $existing = \App\Models\BulletinGeneration::where('student_id', $request->student_id)
                ->where('period_type', $request->type)
                ->where('period_identifier', $request->period_identifier)
                ->first();

            if ($existing) {
                if ($existing->file_path && file_exists(storage_path('app/' . $existing->file_path))) {
                    unlink(storage_path('app/' . $existing->file_path));
                }
                $existing->update([
                    'file_path' => $filePath,
                    'generated_at' => now(),
                    'is_complete' => true,
                    'completion_percentage' => 100.0,
                ]);
            } else {
                \App\Models\BulletinGeneration::create([
                    'student_id' => $request->student_id,
                    'template_id' => $template->id,
                    'period_type' => $request->type,
                    'period_identifier' => $request->period_identifier,
                    'file_path' => $filePath,
                    'generated_at' => now(),
                    'is_complete' => true,
                    'completion_percentage' => 100.0,
                ]);
            }

            gc_collect_cycles();

            return response()->json([
                'success' => true,
                'message' => 'Bulletin regenere avec les modifications',
                'has_modifications' => $modification !== null,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur regeneration avec modifications: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate bulletin data based on type
     */
    private function generateBulletinData(string $type, string $periodIdentifier, int $studentId): ?array
    {
        if ($type === 'sequence') {
            $seqNum = (int) str_replace('seq', '', $periodIdentifier);
            return $this->bulletinService->generateSequenceBulletinData($seqNum, $studentId);
        } elseif ($type === 'trimester') {
            $trimNum = (int) str_replace('trim', '', $periodIdentifier);
            return $this->bulletinService->generateTrimesterBulletinData($trimNum, $studentId);
        } elseif ($type === 'annual') {
            $student = Student::findOrFail($studentId);
            if ($this->bulletinService->isApcClass($student)) {
                return $this->bulletinService->generateAnnualBulletinData($studentId);
            }
            return $this->bulletinService->generateAnnualBulletinDataNonApc($studentId);
        }
        return null;
    }

    /**
     * Build editable data from bulletin data
     */
    private function buildEditableData(array $bulletinData, string $type): array
    {
        $subjects = [];

        if (isset($bulletinData['subject_groups'])) {
            foreach ($bulletinData['subject_groups'] as $groupName => $groupSubjects) {
                foreach ($groupSubjects as $subject) {
                    $subjectEntry = [
                        'subject_id' => $subject['subject_id'] ?? null,
                        'name' => $subject['name'] ?? '',
                        'coefficient' => $subject['coefficient'] ?? 1,
                        'teacher' => $subject['teacher'] ?? '',
                    ];

                    if ($type === 'sequence') {
                        $subjectEntry['score'] = $subject['score'] ?? null;
                    } elseif ($type === 'trimester') {
                        $cycleType = $subject['cycle_type'] ?? 'premier';
                        if ($cycleType === 'deuxieme') {
                            $subjectEntry['sequence1'] = $subject['sequence1'] ?? null;
                            $subjectEntry['sequence2'] = $subject['sequence2'] ?? null;
                            $subjectEntry['composition'] = $subject['composition'] ?? null;
                            $subjectEntry['score'] = $subject['score'] ?? $subject['average'] ?? null;
                        } else {
                            $subjectEntry['ds'] = $subject['ds'] ?? null;
                            $subjectEntry['composition'] = $subject['composition'] ?? null;
                            $subjectEntry['score'] = $subject['score'] ?? $subject['average'] ?? null;
                        }
                        $subjectEntry['cycle_type'] = $cycleType;
                    } elseif ($type === 'annual') {
                        $subjectEntry['trim1'] = $subject['trim1'] ?? $subject['trimester1_average'] ?? null;
                        $subjectEntry['trim2'] = $subject['trim2'] ?? $subject['trimester2_average'] ?? null;
                        $subjectEntry['trim3'] = $subject['trim3'] ?? $subject['trimester3_average'] ?? null;
                        $subjectEntry['score'] = $subject['score'] ?? $subject['annual_average'] ?? null;
                    }

                    $subjectEntry['group'] = $groupName;
                    $subjects[] = $subjectEntry;
                }
            }
        }

        return [
            'student' => [
                'id' => $bulletinData['student_id'] ?? null,
                'first_name' => $bulletinData['student_first_name'] ?? '',
                'last_name' => $bulletinData['student_last_name'] ?? '',
                'class_name' => $bulletinData['class_name'] ?? '',
            ],
            'subjects' => $subjects,
            'general' => [
                'average' => $bulletinData['average'] ?? null,
                'rank' => $bulletinData['rank'] ?? null,
                'appreciation' => $bulletinData['appreciation'] ?? '',
                'class_average' => $bulletinData['class_average'] ?? null,
            ],
            'discipline' => $bulletinData['discipline'] ?? [
                'absences_justified' => 0,
                'absences_unjustified' => 0,
                'delays_justified' => 0,
                'delays_unjustified' => 0,
                'blame_conduct' => '',
                'blame_work' => '',
                'warning_conduct' => '',
                'warning_work' => '',
                'detention_hours' => 0,
                'exclusion_days' => 0,
                'observations' => '',
            ],
        ];
    }

    /**
     * Apply modifications to bulletin data
     */
    public function applyModifications(array $bulletinData, array $modifications, string $type): array
    {
        // Apply subject modifications
        if (isset($modifications['subjects']) && isset($bulletinData['subject_groups'])) {
            $subjectMods = collect($modifications['subjects'])->keyBy('subject_id');

            foreach ($bulletinData['subject_groups'] as $groupName => &$groupSubjects) {
                foreach ($groupSubjects as &$subject) {
                    $subjectId = $subject['subject_id'] ?? null;
                    if ($subjectId && $subjectMods->has($subjectId)) {
                        $mod = $subjectMods[$subjectId];

                        if ($type === 'sequence') {
                            if (isset($mod['score']) && $mod['score'] !== null) {
                                $subject['score'] = (float) $mod['score'];
                                $subject['total'] = (float) $mod['score'] * ($subject['coefficient'] ?? 1);
                            }
                        } elseif ($type === 'trimester') {
                            $cycleType = $subject['cycle_type'] ?? 'premier';
                            if ($cycleType === 'deuxieme') {
                                if (array_key_exists('sequence1', $mod)) $subject['sequence1'] = $mod['sequence1'] !== null ? (float) $mod['sequence1'] : null;
                                if (array_key_exists('sequence2', $mod)) $subject['sequence2'] = $mod['sequence2'] !== null ? (float) $mod['sequence2'] : null;
                            } else {
                                if (array_key_exists('ds', $mod)) $subject['ds'] = $mod['ds'] !== null ? (float) $mod['ds'] : null;
                            }
                            if (array_key_exists('composition', $mod)) $subject['composition'] = $mod['composition'] !== null ? (float) $mod['composition'] : null;

                            // Recalculate score
                            if (isset($mod['score']) && $mod['score'] !== null) {
                                $subject['score'] = (float) $mod['score'];
                                $subject['average'] = (float) $mod['score'];
                            } else {
                                // Auto-recalculate from components
                                $this->recalculateSubjectScore($subject, $cycleType);
                            }
                            $subject['total'] = ($subject['score'] ?? 0) * ($subject['coefficient'] ?? 1);
                            $subject['nxc'] = $subject['total'];
                        } elseif ($type === 'annual') {
                            if (array_key_exists('trim1', $mod)) {
                                $subject['trim1'] = $mod['trim1'] !== null ? (float) $mod['trim1'] : null;
                                $subject['trimester1_average'] = $subject['trim1'];
                            }
                            if (array_key_exists('trim2', $mod)) {
                                $subject['trim2'] = $mod['trim2'] !== null ? (float) $mod['trim2'] : null;
                                $subject['trimester2_average'] = $subject['trim2'];
                            }
                            if (array_key_exists('trim3', $mod)) {
                                $subject['trim3'] = $mod['trim3'] !== null ? (float) $mod['trim3'] : null;
                                $subject['trimester3_average'] = $subject['trim3'];
                            }
                            if (isset($mod['score']) && $mod['score'] !== null) {
                                $subject['score'] = (float) $mod['score'];
                                $subject['annual_average'] = (float) $mod['score'];
                                $subject['average'] = (float) $mod['score'];
                            }
                            $subject['total'] = ($subject['score'] ?? $subject['annual_average'] ?? 0) * ($subject['coefficient'] ?? 1);
                        }
                    }
                }
            }
            unset($groupSubjects, $subject);

            // Recalculate general average and total
            $this->recalculateGeneralAverage($bulletinData);
        }

        // Apply appreciation override
        if (isset($modifications['general']['appreciation'])) {
            $bulletinData['appreciation'] = $modifications['general']['appreciation'];
        }

        // Apply discipline overrides
        if (isset($modifications['discipline'])) {
            if (!isset($bulletinData['discipline'])) {
                $bulletinData['discipline'] = [];
            }
            foreach ($modifications['discipline'] as $key => $value) {
                $bulletinData['discipline'][$key] = $value;
            }
        }

        return $bulletinData;
    }

    /**
     * Recalculate subject score from components
     */
    private function recalculateSubjectScore(array &$subject, string $cycleType): void
    {
        if ($cycleType === 'deuxieme') {
            $seq1 = $subject['sequence1'] ?? null;
            $seq2 = $subject['sequence2'] ?? null;
            $comp = $subject['composition'] ?? null;
            $values = array_filter([$seq1, $seq2, $comp], fn($v) => $v !== null);
            if (count($values) > 0) {
                $sum = ($seq1 ?? 0) + ($seq2 ?? 0) + ($comp ?? 0);
                $subject['score'] = round($sum / 3, 2);
                $subject['average'] = $subject['score'];
            }
        } else {
            $ds = $subject['ds'] ?? null;
            $comp = $subject['composition'] ?? null;
            if ($ds !== null || $comp !== null) {
                $subject['score'] = round((($ds ?? 0) + ($comp ?? 0)) / 2, 2);
                $subject['average'] = $subject['score'];
            }
        }
    }

    /**
     * Recalculate general average from modified subjects
     */
    private function recalculateGeneralAverage(array &$bulletinData): void
    {
        $totalPoints = 0;
        $totalCoef = 0;

        foreach ($bulletinData['subject_groups'] as $groupSubjects) {
            foreach ($groupSubjects as $subject) {
                $score = $subject['score'] ?? $subject['average'] ?? null;
                $coef = $subject['coefficient'] ?? 1;
                if ($score !== null && $score !== 'ABS' && is_numeric($score)) {
                    $totalPoints += (float) $score * (float) $coef;
                    $totalCoef += (float) $coef;
                }
            }
        }

        if ($totalCoef > 0) {
            $bulletinData['average'] = round($totalPoints / $totalCoef, 2);
            $bulletinData['total_points'] = round($totalPoints, 2);
            $bulletinData['total_coefficient'] = $totalCoef;

            // Update mention
            $avg = $bulletinData['average'];
            if ($avg >= 16) $bulletinData['mention'] = 'Tres Bien';
            elseif ($avg >= 14) $bulletinData['mention'] = 'Bien';
            elseif ($avg >= 12) $bulletinData['mention'] = 'Assez Bien';
            elseif ($avg >= 10) $bulletinData['mention'] = 'Passable';
            else $bulletinData['mention'] = 'Insuffisant';
        }
    }
}
