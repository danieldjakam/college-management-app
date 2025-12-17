<?php

namespace App\Http\Controllers;

use App\Services\PVService;
use App\Models\ClassSeries;
use App\Models\Evaluation;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PVController extends Controller
{
    protected $pvService;

    public function __construct(PVService $pvService)
    {
        $this->pvService = $pvService;
    }

    /**
     * Générer le PV pour une série de classe et une période d'évaluation
     * GET /api/pv/generate/{classSeriesId}/{periodId}
     * periodId format: "sequenceId_trimesterId" ou "sequenceId/trimesterId"
     */
    public function generate($classSeriesId, $periodId)
    {
        try {
            Log::info("🎯 Requête génération PV - ClassSeries: {$classSeriesId}, Period: {$periodId}");

            // Valider que la série existe
            $classSeries = ClassSeries::findOrFail($classSeriesId);

            // Parser le periodId (format: "sequenceId_trimesterId")
            $parts = explode('_', $periodId);
            if (count($parts) !== 2) {
                throw new \Exception("Format de période invalide. Attendu: sequenceId_trimesterId");
            }

            $sequenceId = (int) $parts[0];
            $trimesterId = (int) $parts[1];

            // Valider que la séquence et le trimestre existent
            $sequence = \App\Models\Sequence::findOrFail($sequenceId);
            $trimester = \App\Models\Trimester::findOrFail($trimesterId);

            // Générer le PV basé sur la période
            $pdf = $this->pvService->generatePVByPeriod($classSeriesId, $sequenceId, $trimesterId);

            // Nom du fichier
            $periodType = $sequence->is_composition ? 'Comp' : 'Seq';
            $periodNumber = $sequence->is_composition ? $trimester->number : $sequence->number;
            $fileName = 'PV_' . str_replace(' ', '_', $classSeries->name) . '_' . $periodType . $periodNumber . '_T' . $trimester->number . '_' . date('Y-m-d') . '.pdf';

            Log::info("✅ PV généré avec succès: {$fileName}");

            // Retourner le PDF
            return $pdf->download($fileName);

        } catch (\Exception $e) {
            Log::error("❌ Erreur génération PV: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PV',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir la liste des périodes d'évaluation disponibles pour une série
     * GET /api/pv/evaluations/{classSeriesId}
     */
    public function getAvailableEvaluations($classSeriesId)
    {
        try {
            $classSeries = ClassSeries::with(['schoolClass'])->findOrFail($classSeriesId);

            // Récupérer l'année scolaire courante
            $currentYear = SchoolYear::where('is_current', true)->first();

            if (!$currentYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active'
                ], 404);
            }

            // Récupérer les périodes uniques (séquences) pour cette année
            // On utilise DISTINCT pour éviter les doublons
            $periods = \DB::table('evaluations')
                ->select([
                    'sequence_id',
                    'trimester_id',
                    'school_year_id'
                ])
                ->where('school_year_id', $currentYear->id)
                ->whereNotNull('sequence_id')
                ->whereNotNull('trimester_id')
                ->distinct()
                ->orderBy('trimester_id')
                ->orderBy('sequence_id')
                ->get();

            // Enrichir avec les relations
            $evaluations = [];
            foreach ($periods as $period) {
                $sequence = \App\Models\Sequence::find($period->sequence_id);
                $trimester = \App\Models\Trimester::find($period->trimester_id);

                if ($sequence && $trimester) {
                    $evaluations[] = [
                        'id' => "{$period->sequence_id}_{$period->trimester_id}", // ID composite
                        'sequence_id' => $period->sequence_id,
                        'trimester_id' => $period->trimester_id,
                        'school_year_id' => $period->school_year_id,
                        'sequence' => $sequence,
                        'trimester' => $trimester
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'class_series' => $classSeries,
                    'evaluations' => $evaluations,
                    'school_year' => $currentYear
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Erreur récupération évaluations: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des évaluations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir la liste de toutes les séries de classes
     * GET /api/pv/class-series
     */
    public function getClassSeries(Request $request)
    {
        try {
            $query = ClassSeries::with(['schoolClass.level'])
                ->where('is_active', true);

            // Filtrer par niveau si spécifié
            if ($request->has('level_id')) {
                $query->whereHas('schoolClass', function($q) use ($request) {
                    $q->where('level_id', $request->level_id);
                });
            }

            $classSeries = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $classSeries
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Erreur récupération séries: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des séries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prévisualiser le PV (retourner en HTML pour debug)
     * GET /api/pv/preview/{classSeriesId}/{periodId}
     * periodId format: "sequenceId_trimesterId"
     */
    public function preview($classSeriesId, $periodId)
    {
        try {
            $classSeries = ClassSeries::findOrFail($classSeriesId);

            // Parser le periodId
            $parts = explode('_', $periodId);
            if (count($parts) !== 2) {
                throw new \Exception("Format de période invalide. Attendu: sequenceId_trimesterId");
            }

            $sequenceId = (int) $parts[0];
            $trimesterId = (int) $parts[1];

            // Valider que la séquence et le trimestre existent
            $sequence = \App\Models\Sequence::findOrFail($sequenceId);
            $trimester = \App\Models\Trimester::findOrFail($trimesterId);

            // Générer le HTML de prévisualisation
            $html = $this->pvService->generateHTMLByPeriod($classSeriesId, $sequenceId, $trimesterId);

            return response($html, 200)->header('Content-Type', 'text/html');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la prévisualisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le PV de TRIMESTRE (moyennes DS + Composition)
     * GET /api/pv/trimester/generate/{classSeriesId}/{trimesterId}
     */
    public function generateTrimester($classSeriesId, $trimesterId)
    {
        try {
            Log::info("🎯 Requête génération PV TRIMESTRE - ClassSeries: {$classSeriesId}, Trimester: {$trimesterId}");

            // Valider que la série existe
            $classSeries = ClassSeries::findOrFail($classSeriesId);

            // Valider que le trimestre existe
            $trimester = \App\Models\Trimester::findOrFail($trimesterId);

            // Générer le PV de trimestre
            $pdf = $this->pvService->generateTrimesterPV($classSeriesId, $trimesterId);

            // Nom du fichier
            $fileName = 'PV_' . str_replace(' ', '_', $classSeries->name) . '_Trimestre' . $trimester->number . '_' . date('Y-m-d') . '.pdf';

            Log::info("✅ PV TRIMESTRE généré avec succès: {$fileName}");

            // Retourner le PDF
            return $pdf->download($fileName);

        } catch (\Exception $e) {
            Log::error("❌ Erreur génération PV TRIMESTRE: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PV de trimestre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prévisualiser le PV de TRIMESTRE (retourner en HTML)
     * GET /api/pv/trimester/preview/{classSeriesId}/{trimesterId}
     */
    public function previewTrimester($classSeriesId, $trimesterId)
    {
        try {
            $classSeries = ClassSeries::findOrFail($classSeriesId);
            $trimester = \App\Models\Trimester::findOrFail($trimesterId);

            // Générer le HTML de prévisualisation
            $html = $this->pvService->generateTrimesterHTML($classSeriesId, $trimesterId);

            return response($html, 200)->header('Content-Type', 'text/html');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la prévisualisation du PV de trimestre',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir la liste des trimestres disponibles pour une série
     * GET /api/pv/trimesters/{classSeriesId}
     */
    public function getAvailableTrimesters($classSeriesId)
    {
        try {
            $classSeries = ClassSeries::with(['schoolClass'])->findOrFail($classSeriesId);

            // Récupérer l'année scolaire courante
            $currentYear = SchoolYear::where('is_current', true)->first();

            if (!$currentYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année scolaire active'
                ], 404);
            }

            // Récupérer tous les trimestres de l'année courante
            $trimesters = \App\Models\Trimester::where('school_year_id', $currentYear->id)
                ->orderBy('number')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'class_series' => $classSeries,
                    'trimesters' => $trimesters,
                    'school_year' => $currentYear
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Erreur récupération trimestres: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des trimestres',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
