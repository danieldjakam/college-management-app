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
     * Générer le PV pour une série de classe et une évaluation
     * GET /api/pv/generate/{classSeriesId}/{evaluationId}
     */
    public function generate($classSeriesId, $evaluationId)
    {
        try {
            Log::info("🎯 Requête génération PV - ClassSeries: {$classSeriesId}, Evaluation: {$evaluationId}");

            // Valider que la série et l'évaluation existent
            $classSeries = ClassSeries::findOrFail($classSeriesId);
            $evaluation = Evaluation::findOrFail($evaluationId);

            // Générer le PV
            $pdf = $this->pvService->generatePV($classSeriesId, $evaluationId);

            // Nom du fichier
            $fileName = 'PV_' . str_replace(' ', '_', $classSeries->name) . '_' . date('Y-m-d') . '.pdf';

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
     * Obtenir la liste des évaluations disponibles pour une série
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

            // Récupérer toutes les évaluations de l'année
            $evaluations = Evaluation::where('school_year_id', $currentYear->id)
                ->with(['sequence', 'trimester'])
                ->orderBy('trimester_id')
                ->orderBy('sequence_id')
                ->get();

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
     * GET /api/pv/preview/{classSeriesId}/{evaluationId}
     */
    public function preview($classSeriesId, $evaluationId)
    {
        try {
            $classSeries = ClassSeries::findOrFail($classSeriesId);
            $evaluation = Evaluation::findOrFail($evaluationId);

            // Générer le HTML uniquement (pas de PDF)
            $html = $this->pvService->generateHTML(
                $classSeries,
                $evaluation,
                $this->pvService->getSeriesSubjects($classSeriesId),
                $this->pvService->getStudentsResults($classSeriesId, $evaluationId),
                $this->pvService->getStatistics($classSeriesId, $evaluationId),
                $this->pvService->getMainTeacher($classSeriesId, $evaluation->school_year_id)
            );

            return response($html, 200)->header('Content-Type', 'text/html');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la prévisualisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
