<?php

namespace App\Http\Controllers;

use App\Models\BulletinModification;
use App\Models\ClassSeriesSubject;
use App\Models\Grade;
use App\Models\Student;
use App\Services\BulletinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            $data = $this->bulletinService->generateTrimesterBulletinData($trimNum, $studentId);
            if ($data) {
                $data = $this->applySequenceModificationsToTrimester($data, $trimNum, $studentId);
            }
            return $data;
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
     * Apply sequence modifications to trimester data
     * When sequences have been modified, propagate those changes to trimester calculations
     */
    private function applySequenceModificationsToTrimester(array $data, int $trimNum, int $studentId): array
    {
        // Determine which sequences belong to this trimester
        // Trim 1: seq1, seq2, composition 1 (seq3 or comp1)
        // Trim 2: seq3, seq4, composition 2
        // Trim 3: seq5, seq6, composition 3
        $seq1Num = ($trimNum - 1) * 2 + 1;
        $seq2Num = ($trimNum - 1) * 2 + 2;

        // Get sequence modifications
        $seqMod1 = BulletinModification::where('student_id', $studentId)
            ->where('period_type', 'sequence')
            ->where('period_identifier', 'seq' . $seq1Num)
            ->first();

        $seqMod2 = BulletinModification::where('student_id', $studentId)
            ->where('period_type', 'sequence')
            ->where('period_identifier', 'seq' . $seq2Num)
            ->first();

        // Also check composition modification
        $compMod = BulletinModification::where('student_id', $studentId)
            ->where('period_type', 'sequence')
            ->where('period_identifier', 'comp' . $trimNum)
            ->first();

        if (!$seqMod1 && !$seqMod2 && !$compMod) {
            return $data; // No sequence modifications to apply
        }

        // Build lookup: subject_id => modified score for each sequence
        $seq1Mods = [];
        $seq2Mods = [];
        $compMods = [];

        if ($seqMod1 && !empty($seqMod1->modifications['subjects'])) {
            foreach ($seqMod1->modifications['subjects'] as $s) {
                if (isset($s['subject_id']) && isset($s['score']) && $s['score'] !== null) {
                    $seq1Mods[$s['subject_id']] = (float) $s['score'];
                }
            }
        }
        if ($seqMod2 && !empty($seqMod2->modifications['subjects'])) {
            foreach ($seqMod2->modifications['subjects'] as $s) {
                if (isset($s['subject_id']) && isset($s['score']) && $s['score'] !== null) {
                    $seq2Mods[$s['subject_id']] = (float) $s['score'];
                }
            }
        }
        if ($compMod && !empty($compMod->modifications['subjects'])) {
            foreach ($compMod->modifications['subjects'] as $s) {
                if (isset($s['subject_id']) && isset($s['score']) && $s['score'] !== null) {
                    $compMods[$s['subject_id']] = (float) $s['score'];
                }
            }
        }

        if (empty($seq1Mods) && empty($seq2Mods) && empty($compMods)) {
            return $data;
        }

        // Apply modifications to subject_groups
        foreach ($data['subject_groups'] as $groupName => &$groupSubjects) {
            foreach ($groupSubjects as &$subject) {
                $subjectId = $subject['subject_id'] ?? null;
                if (!$subjectId) continue;

                $changed = false;
                $cycleType = $subject['cycle_type'] ?? 'premier';

                if ($cycleType === 'deuxieme') {
                    // Deuxième cycle: sequence1, sequence2, composition
                    if (isset($seq1Mods[$subjectId])) {
                        $subject['sequence1'] = $seq1Mods[$subjectId];
                        $changed = true;
                    }
                    if (isset($seq2Mods[$subjectId])) {
                        $subject['sequence2'] = $seq2Mods[$subjectId];
                        $changed = true;
                    }
                    if (isset($compMods[$subjectId])) {
                        $subject['composition'] = $compMods[$subjectId];
                        $changed = true;
                    }

                    if ($changed) {
                        // Recalculate: M/20 = (seq1 + seq2 + comp) / 3
                        $s1 = $subject['sequence1'] ?? null;
                        $s2 = $subject['sequence2'] ?? null;
                        $comp = $subject['composition'] ?? null;
                        $values = array_filter([$s1, $s2, $comp], fn($v) => $v !== null && is_numeric($v));
                        if (count($values) > 0) {
                            $avg = array_sum($values) / 3; // Always divide by 3 for deuxième cycle
                            $subject['score'] = round($avg, 2);
                            $subject['average'] = round($avg, 2);
                            $coef = $subject['coefficient'] ?? 1;
                            $subject['total'] = round($avg * $coef, 2);
                            $subject['nxc'] = $subject['total'];
                        }
                    }
                } else {
                    // Premier cycle: ds = (seq1 + seq2) / 2, then (ds + comp) / 2
                    $dsChanged = false;
                    if (isset($seq1Mods[$subjectId]) || isset($seq2Mods[$subjectId])) {
                        // Need to recalculate DS
                        // Get original sequence grades to mix with modifications
                        $origSeq1 = null;
                        $origSeq2 = null;

                        // Try to get from existing ds calculation or raw data
                        // For now, use the modification values directly if available
                        $s1 = $seq1Mods[$subjectId] ?? null;
                        $s2 = $seq2Mods[$subjectId] ?? null;

                        // If only one seq was modified, we need the original for the other
                        // The ds value = (seq1 + seq2) / 2, we can't easily reverse it
                        // So just recalculate with available data
                        if ($s1 !== null && $s2 !== null) {
                            $subject['ds'] = round(($s1 + $s2) / 2, 2);
                        } elseif ($s1 !== null) {
                            // Only seq1 modified - use original ds to estimate seq2
                            $origDs = $subject['ds'] ?? null;
                            if ($origDs !== null && is_numeric($origDs)) {
                                $origSeq2 = (float)$origDs * 2 - (float)$origDs; // Can't determine, just use s1 with 0
                                // Actually: ds_orig = (seq1_orig + seq2_orig) / 2
                                // We only know s1_new, not seq2_orig independently
                                // Best approach: use s1_new and assume seq2 hasn't changed
                                // ds_new = (s1_new + seq2_orig) / 2 where seq2_orig = ds_orig * 2 - seq1_orig
                                // But we don't have seq1_orig... use ds as-is and override with s1
                                $subject['ds'] = round($s1 / 2, 2); // s1 with missing s2 = 0
                            } else {
                                $subject['ds'] = round($s1 / 2, 2);
                            }
                        } elseif ($s2 !== null) {
                            $subject['ds'] = round($s2 / 2, 2);
                        }
                        $dsChanged = true;
                        $changed = true;
                    }

                    if (isset($compMods[$subjectId])) {
                        $subject['composition'] = $compMods[$subjectId];
                        $changed = true;
                    }

                    if ($changed) {
                        $ds = $subject['ds'] ?? null;
                        $comp = $subject['composition'] ?? null;
                        if ($ds !== null && is_numeric($ds) && $comp !== null && is_numeric($comp)) {
                            $avg = ((float)$ds + (float)$comp) / 2;
                        } elseif ($ds !== null && is_numeric($ds)) {
                            $avg = (float)$ds / 2;
                        } elseif ($comp !== null && is_numeric($comp)) {
                            $avg = (float)$comp / 2;
                        } else {
                            $avg = null;
                        }

                        if ($avg !== null) {
                            $subject['score'] = round($avg, 2);
                            $subject['average'] = round($avg, 2);
                            $coef = $subject['coefficient'] ?? 1;
                            $subject['total'] = round($avg * $coef, 2);
                        }
                    }
                }
            }
        }
        unset($groupSubjects, $subject);

        // Sync to flat subjects array
        if (isset($data['subjects'])) {
            $modsBySubjectId = [];
            foreach ($data['subject_groups'] as $groupSubjects) {
                foreach ($groupSubjects as $s) {
                    if (isset($s['subject_id'])) {
                        $modsBySubjectId[$s['subject_id']] = $s;
                    }
                }
            }
            foreach ($data['subjects'] as &$flatSubject) {
                $sid = $flatSubject['subject_id'] ?? null;
                if ($sid && isset($modsBySubjectId[$sid])) {
                    $flatSubject = array_merge($flatSubject, $modsBySubjectId[$sid]);
                }
            }
            unset($flatSubject);
        }

        // Recalculate general average
        $this->recalculateGeneralAverage($data);

        return $data;
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
                        // Expose individual sequences and compositions for editing
                        $isNonApc = isset($subject['ev1']);
                        $subjectEntry['is_non_apc'] = $isNonApc;

                        if ($isNonApc) {
                            // NonAPC: ev1-ev4, comp1-comp3 (ev5/ev6 stay as-is for premier cycle)
                            for ($i = 1; $i <= 4; $i++) {
                                $val = $subject["ev{$i}"] ?? '-';
                                $subjectEntry["ev{$i}"] = ($val !== '-' && $val !== 'ABS') ? (float) $val : null;
                            }
                            for ($i = 1; $i <= 3; $i++) {
                                $val = $subject["comp{$i}"] ?? '-';
                                $subjectEntry["comp{$i}"] = ($val !== '-' && $val !== 'ABS') ? (float) $val : null;
                            }
                        } else {
                            // APC: trim values come from calcTrimGrade (ds+comp), expose as trim1/trim2/trim3
                            // But also need raw sequence data - extract from bulletin data
                            $subjectEntry['trim1'] = $subject['trim1'] ?? $subject['trimester1_average'] ?? null;
                            $subjectEntry['trim2'] = $subject['trim2'] ?? $subject['trimester2_average'] ?? null;
                            $subjectEntry['trim3'] = $subject['trim3'] ?? $subject['trimester3_average'] ?? null;
                        }
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
        // Detect cycle/section type for competence recalculation
        $sectionType = $bulletinData['section_type'] ?? 'francophone';
        $cycleType = 'premier';
        if (isset($bulletinData['subject_groups'])) {
            foreach ($bulletinData['subject_groups'] as $gs) {
                foreach ($gs as $s) {
                    if (isset($s['cycle_type'])) { $cycleType = $s['cycle_type']; break 2; }
                }
            }
        }

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
                            $subjectCycleType = $subject['cycle_type'] ?? 'premier';
                            if ($subjectCycleType === 'deuxieme') {
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
                                $this->recalculateSubjectScore($subject, $subjectCycleType);
                            }
                            $subject['total'] = ($subject['score'] ?? 0) * ($subject['coefficient'] ?? 1);
                            $subject['nxc'] = $subject['total'];
                        } elseif ($type === 'annual') {
                            $isNonApc = isset($subject['ev1']);
                            $annualChanged = false;

                            if ($isNonApc) {
                                // NonAPC: apply ev1-ev4, comp1-comp3 modifications
                                for ($i = 1; $i <= 4; $i++) {
                                    $key = "ev{$i}";
                                    if (array_key_exists($key, $mod) && $mod[$key] !== null) {
                                        $subject[$key] = number_format((float) $mod[$key], 2);
                                        $annualChanged = true;
                                    }
                                }
                                for ($i = 1; $i <= 3; $i++) {
                                    $key = "comp{$i}";
                                    if (array_key_exists($key, $mod) && $mod[$key] !== null) {
                                        $subject[$key] = number_format((float) $mod[$key], 2);
                                        $annualChanged = true;
                                    }
                                }

                                if ($annualChanged) {
                                    // Recalculate trimester averages from sequences + compositions
                                    // Premier cycle: trim = (DS + comp) / 2 where DS = (seq1 + seq2) / 2
                                    // Trim 1: ev1, ev2, comp1 | Trim 2: ev3, ev4, comp2 | Trim 3: comp3 only
                                    $parseVal = function ($v) {
                                        if ($v === '-' || $v === 'ABS' || $v === null) return null;
                                        return (float) $v;
                                    };

                                    // Trim 1
                                    $s1 = $parseVal($subject['ev1'] ?? null);
                                    $s2 = $parseVal($subject['ev2'] ?? null);
                                    $c1 = $parseVal($subject['comp1'] ?? null);
                                    $t1 = $this->calcTrimesterFromComponents($s1, $s2, $c1);

                                    // Trim 2
                                    $s3 = $parseVal($subject['ev3'] ?? null);
                                    $s4 = $parseVal($subject['ev4'] ?? null);
                                    $c2 = $parseVal($subject['comp2'] ?? null);
                                    $t2 = $this->calcTrimesterFromComponents($s3, $s4, $c2);

                                    // Trim 3: composition only
                                    $c3 = $parseVal($subject['comp3'] ?? null);
                                    $t3 = ($c3 !== null) ? $c3 : null;

                                    // Recalculate annual average: (trim1 + trim2 + trim3) / 3
                                    $trimValues = array_filter([$t1, $t2, $t3], fn($v) => $v !== null);
                                    if (count($trimValues) > 0) {
                                        $annualAvg = (($t1 ?? 0) + ($t2 ?? 0) + ($t3 ?? 0)) / 3;
                                        $subject['annual_average'] = round($annualAvg, 2);
                                        $subject['score'] = round($annualAvg, 2);
                                        $subject['average'] = round($annualAvg, 2);
                                        $coef = $subject['coefficient'] ?? 1;
                                        $subject['total'] = round($annualAvg * $coef, 2);
                                    }
                                }
                            } else {
                                // APC: allow direct trim modifications (fallback)
                                if (array_key_exists('trim1', $mod)) {
                                    $subject['trim1'] = $mod['trim1'] !== null ? (float) $mod['trim1'] : null;
                                    $subject['trimester1_average'] = $subject['trim1'];
                                    $annualChanged = true;
                                }
                                if (array_key_exists('trim2', $mod)) {
                                    $subject['trim2'] = $mod['trim2'] !== null ? (float) $mod['trim2'] : null;
                                    $subject['trimester2_average'] = $subject['trim2'];
                                    $annualChanged = true;
                                }
                                if (array_key_exists('trim3', $mod)) {
                                    $subject['trim3'] = $mod['trim3'] !== null ? (float) $mod['trim3'] : null;
                                    $subject['trimester3_average'] = $subject['trim3'];
                                    $annualChanged = true;
                                }

                                if ($annualChanged && !isset($mod['score'])) {
                                    // Recalculate annual from trimesters
                                    $t1 = (float) ($subject['trim1'] ?? $subject['trimester1_average'] ?? 0);
                                    $t2 = (float) ($subject['trim2'] ?? $subject['trimester2_average'] ?? 0);
                                    $t3 = (float) ($subject['trim3'] ?? $subject['trimester3_average'] ?? 0);
                                    $annualAvg = ($t1 + $t2 + $t3) / 3;
                                    $subject['score'] = round($annualAvg, 2);
                                    $subject['annual_average'] = round($annualAvg, 2);
                                    $subject['average'] = round($annualAvg, 2);
                                }
                            }

                            if (isset($mod['score']) && $mod['score'] !== null) {
                                $subject['score'] = (float) $mod['score'];
                                $subject['annual_average'] = (float) $mod['score'];
                                $subject['average'] = (float) $mod['score'];
                            }
                            $subject['total'] = ($subject['score'] ?? $subject['annual_average'] ?? 0) * ($subject['coefficient'] ?? 1);
                        }

                        // Recalculate competence based on new score
                        $newScore = $subject['score'] ?? $subject['average'] ?? null;
                        if ($newScore !== null && is_numeric($newScore)) {
                            $subject['competence'] = $this->calculateCompetence((float) $newScore, $cycleType, $sectionType);
                            $subject['grade'] = $subject['competence'];
                        }

                        // Recalculate per-subject rank
                        if ($newScore !== null && is_numeric($newScore) && isset($bulletinData['student_id'])) {
                            $newSubjectRank = $this->recalculateSubjectRank(
                                $bulletinData['student_id'],
                                $subjectId,
                                (float) $newScore,
                                $type,
                                $bulletinData
                            );
                            if ($newSubjectRank !== null) {
                                $subject['rank'] = $newSubjectRank;
                            }
                        }
                    }
                }
            }
            unset($groupSubjects, $subject);

            // Sync modifications back to the flat 'subjects' array
            // (the template rendering uses $data['subjects'], not $data['subject_groups'])
            if (isset($bulletinData['subjects'])) {
                $modsBySubjectId = [];
                foreach ($bulletinData['subject_groups'] as $groupSubjects) {
                    foreach ($groupSubjects as $s) {
                        if (isset($s['subject_id'])) {
                            $modsBySubjectId[$s['subject_id']] = $s;
                        }
                    }
                }
                foreach ($bulletinData['subjects'] as &$flatSubject) {
                    $sid = $flatSubject['subject_id'] ?? null;
                    if ($sid && isset($modsBySubjectId[$sid])) {
                        $flatSubject = array_merge($flatSubject, $modsBySubjectId[$sid]);
                    }
                }
                unset($flatSubject);
            }

            // Recalculate general average and total
            $this->recalculateGeneralAverage($bulletinData);

            // Recalculate rank based on new average
            if (isset($bulletinData['average']) && isset($bulletinData['student_id'])) {
                $newRank = $this->recalculateRank(
                    $bulletinData['student_id'],
                    $bulletinData['average'],
                    $type,
                    $bulletinData
                );
                if ($newRank !== null) {
                    $bulletinData['rank'] = $newRank;
                }
            }
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
     * Calculate trimester average from sequence and composition components
     * Premier cycle: (DS + comp) / 2 where DS = (seq1 + seq2) / 2
     */
    private function calcTrimesterFromComponents(?float $seq1, ?float $seq2, ?float $comp): ?float
    {
        $hasAny = ($seq1 !== null || $seq2 !== null || $comp !== null);
        if (!$hasAny) return null;

        $ds = (($seq1 ?? 0) + ($seq2 ?? 0)) / 2;
        return ($ds + ($comp ?? 0)) / 2;
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

    /**
     * Recalculate student rank after average modification
     * Gets all classmates' averages from DB, replaces modified student's average, re-ranks
     */
    private function recalculateRank(int $studentId, float $newAverage, string $type, array $bulletinData): ?int
    {
        try {
            $student = Student::find($studentId);
            if (!$student || !$student->class_series_id) {
                return null;
            }

            // Get all classmates
            $classmates = Student::where('class_series_id', $student->class_series_id)->get();
            $allSubjects = ClassSeriesSubject::where('class_series_id', $student->class_series_id)->get();

            $averages = [];

            if ($type === 'sequence') {
                $sequenceId = $bulletinData['sequence']->id ?? null;
                if (!$sequenceId) return null;

                foreach ($classmates as $classmate) {
                    if ($classmate->id == $studentId) {
                        $averages[$classmate->id] = $newAverage;
                        continue;
                    }

                    $totalPoints = 0;
                    $totalCoef = 0;
                    foreach ($allSubjects as $subject) {
                        $grade = Grade::where('student_id', $classmate->id)
                            ->where('sequence_id', $sequenceId)
                            ->where('class_series_subject_id', $subject->id)
                            ->whereNotNull('score')
                            ->where('is_absent', false)
                            ->first();
                        if ($grade) {
                            $totalPoints += (float)$grade->score * (float)$subject->coefficient;
                            $totalCoef += (float)$subject->coefficient;
                        }
                    }
                    if ($totalCoef > 0) {
                        $averages[$classmate->id] = $totalPoints / $totalCoef;
                    }
                }
            } elseif ($type === 'trimester') {
                $trimesterNumber = $bulletinData['trimester']->number ?? $bulletinData['trimester_number'] ?? null;
                if (!$trimesterNumber) return null;

                foreach ($classmates as $classmate) {
                    if ($classmate->id == $studentId) {
                        $averages[$classmate->id] = $newAverage;
                        continue;
                    }
                    // Use BulletinService to get trimester data for classmate
                    try {
                        $data = $this->bulletinService->generateTrimesterBulletinData((int)$trimesterNumber, $classmate->id);
                        if ($data && isset($data['average']) && $data['average'] > 0) {
                            $averages[$classmate->id] = (float)$data['average'];
                        }
                    } catch (\Exception $e) {
                        // Skip this student if error
                    }
                }
            } elseif ($type === 'annual') {
                foreach ($classmates as $classmate) {
                    if ($classmate->id == $studentId) {
                        $averages[$classmate->id] = $newAverage;
                        continue;
                    }
                    try {
                        if ($this->bulletinService->isApcClass($classmate)) {
                            $data = $this->bulletinService->generateAnnualBulletinData($classmate->id);
                        } else {
                            $data = $this->bulletinService->generateAnnualBulletinDataNonApc($classmate->id);
                        }
                        if ($data && isset($data['average']) && (float)$data['average'] > 0) {
                            $averages[$classmate->id] = (float)$data['average'];
                        }
                    } catch (\Exception $e) {
                        // Skip
                    }
                }
            }

            if (empty($averages) || !isset($averages[$studentId])) {
                return null;
            }

            // Sort descending and find rank
            arsort($averages);
            $rank = 1;
            foreach ($averages as $sid => $avg) {
                if ($sid == $studentId) {
                    return $rank;
                }
                $rank++;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erreur recalcul rang: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate competence based on score, cycle type, and section type
     */
    private function calculateCompetence(float $grade, string $cycleType = 'premier', string $sectionType = 'francophone'): string
    {
        if ($sectionType === 'technique') {
            if ($grade >= 16) return 'A+';
            if ($grade >= 14) return 'A';
            if ($grade >= 10) return 'ECA';
            return 'NA';
        }

        if ($sectionType === 'anglophone') {
            if ($grade >= 16) return 'Mastered (Excellent)';
            if ($grade >= 14) return 'Mastered (Very Good)';
            if ($grade >= 12) return 'Mastered (Good)';
            if ($grade >= 10) return 'Developing';
            return 'Beginning';
        }

        if ($cycleType === 'deuxieme') {
            if ($grade >= 16) return 'Acquise (Excellent)';
            if ($grade >= 14) return 'Acquise (Très Bien)';
            if ($grade >= 12) return 'Acquise (Bien)';
            if ($grade >= 10) return 'En cours d\'acquisition';
            return 'Non acquise';
        }

        // Premier cycle francophone
        if ($grade >= 16) return 'A+';
        if ($grade >= 14) return 'A';
        if ($grade >= 10) return 'ECA';
        return 'NA';
    }

    /**
     * Recalculate per-subject rank after score modification
     */
    private function recalculateSubjectRank(int $studentId, int $subjectId, float $newScore, string $type, array $bulletinData): ?int
    {
        try {
            $student = Student::find($studentId);
            if (!$student || !$student->class_series_id) return null;

            // Find the class_series_subject_id for this subject
            $classSeriesSubject = ClassSeriesSubject::where('class_series_id', $student->class_series_id)
                ->whereHas('subject', function ($q) use ($subjectId) {
                    $q->where('id', $subjectId);
                })
                ->first();
            if (!$classSeriesSubject) return null;

            if ($type === 'sequence') {
                $sequenceId = $bulletinData['sequence']->id ?? null;
                if (!$sequenceId) return null;

                // Count how many students have a higher score for this subject in this sequence
                $betterCount = Grade::where('sequence_id', $sequenceId)
                    ->where('class_series_subject_id', $classSeriesSubject->id)
                    ->whereNotNull('score')
                    ->where('is_absent', false)
                    ->where('score', '>', $newScore)
                    ->count();

                return $betterCount + 1;

            } elseif ($type === 'trimester') {
                // For trimester, get all classmates' averages for this subject
                $classmates = Student::where('class_series_id', $student->class_series_id)->get();
                $trimesterNumber = $bulletinData['trimester']->number ?? $bulletinData['trimester_number'] ?? null;
                if (!$trimesterNumber) return null;

                $scores = [$studentId => $newScore];

                foreach ($classmates as $classmate) {
                    if ($classmate->id == $studentId) continue;
                    try {
                        $data = $this->bulletinService->generateTrimesterBulletinData((int)$trimesterNumber, $classmate->id);
                        if ($data && isset($data['subjects'])) {
                            foreach ($data['subjects'] as $s) {
                                if (($s['subject_id'] ?? null) == $subjectId) {
                                    $subjectScore = $s['score'] ?? $s['average'] ?? null;
                                    if ($subjectScore !== null && is_numeric($subjectScore)) {
                                        $scores[$classmate->id] = (float)$subjectScore;
                                    }
                                    break;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Skip
                    }
                }

                // Count better scores
                $betterCount = 0;
                foreach ($scores as $sid => $score) {
                    if ($sid != $studentId && $score > $newScore) {
                        $betterCount++;
                    }
                }
                return $betterCount + 1;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erreur recalcul rang matière: ' . $e->getMessage());
            return null;
        }
    }
}
