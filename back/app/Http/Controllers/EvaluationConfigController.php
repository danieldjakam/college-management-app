<?php

namespace App\Http\Controllers;

use App\Models\EvaluationConfig;
use App\Models\SchoolYear;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EvaluationConfigController extends Controller
{
    public function index(Request $request)
    {
        $yearId = $request->get('school_year_id');
        $levelId = $request->get('level_id');
        
        $query = EvaluationConfig::with(['schoolYear', 'level.section']);
        
        if ($yearId) {
            $query->forYear($yearId);
        }
        
        if ($levelId) {
            $query->forLevel($levelId);
        }
        
        $configs = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $configs
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'level_id' => 'required|exists:levels,id',
            'evaluation_mode' => 'required|in:1ds_1comp,2ds_1comp',
            'ds1_percentage' => 'required|numeric|min:0|max:100',
            'ds2_percentage' => 'numeric|min:0|max:100',
            'composition_percentage' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        // Validation des pourcentages
        $total = $request->ds1_percentage + ($request->ds2_percentage ?? 0) + $request->composition_percentage;
        if ($total != 100) {
            return response()->json([
                'success' => false,
                'message' => "Le total des pourcentages doit être 100%. Total actuel: {$total}%"
            ], 422);
        }

        // Si cette config est active, désactiver les autres pour ce niveau/année
        if ($request->get('is_active', true)) {
            EvaluationConfig::deactivateOtherConfigs($request->school_year_id, $request->level_id);
        }

        $config = EvaluationConfig::create([
            'school_year_id' => $request->school_year_id,
            'level_id' => $request->level_id,
            'evaluation_mode' => $request->evaluation_mode,
            'ds1_percentage' => $request->ds1_percentage,
            'ds2_percentage' => $request->ds2_percentage ?? 0,
            'composition_percentage' => $request->composition_percentage,
            'description' => $request->description,
            'is_active' => $request->get('is_active', true)
        ]);

        return response()->json([
            'success' => true,
            'data' => $config->load(['schoolYear', 'level.section']),
            'message' => 'Configuration créée avec succès'
        ], 201);
    }

    public function show(EvaluationConfig $evaluationConfig)
    {
        return response()->json([
            'success' => true,
            'data' => $evaluationConfig->load(['schoolYear', 'level.section'])
        ]);
    }

    public function update(Request $request, EvaluationConfig $evaluationConfig)
    {
        $request->validate([
            'evaluation_mode' => 'required|in:1ds_1comp,2ds_1comp',
            'ds1_percentage' => 'required|numeric|min:0|max:100',
            'ds2_percentage' => 'numeric|min:0|max:100',
            'composition_percentage' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        // Validation des pourcentages
        $total = $request->ds1_percentage + ($request->ds2_percentage ?? 0) + $request->composition_percentage;
        if ($total != 100) {
            return response()->json([
                'success' => false,
                'message' => "Le total des pourcentages doit être 100%. Total actuel: {$total}%"
            ], 422);
        }

        // Si cette config devient active, désactiver les autres pour ce niveau/année
        if ($request->get('is_active', true)) {
            EvaluationConfig::deactivateOtherConfigs(
                $evaluationConfig->school_year_id, 
                $evaluationConfig->level_id, 
                $evaluationConfig->id
            );
        }

        $evaluationConfig->update([
            'evaluation_mode' => $request->evaluation_mode,
            'ds1_percentage' => $request->ds1_percentage,
            'ds2_percentage' => $request->ds2_percentage ?? 0,
            'composition_percentage' => $request->composition_percentage,
            'description' => $request->description,
            'is_active' => $request->get('is_active', true)
        ]);

        return response()->json([
            'success' => true,
            'data' => $evaluationConfig->load(['schoolYear', 'level.section']),
            'message' => 'Configuration mise à jour avec succès'
        ]);
    }

    public function destroy(EvaluationConfig $evaluationConfig)
    {
        $evaluationConfig->delete();

        return response()->json([
            'success' => true,
            'message' => 'Configuration supprimée avec succès'
        ]);
    }

    public function toggleStatus(EvaluationConfig $evaluationConfig)
    {
        $newStatus = !$evaluationConfig->is_active;
        
        // Si on active cette config, désactiver les autres pour ce niveau/année
        if ($newStatus) {
            EvaluationConfig::deactivateOtherConfigs(
                $evaluationConfig->school_year_id, 
                $evaluationConfig->level_id, 
                $evaluationConfig->id
            );
        }
        
        $evaluationConfig->update(['is_active' => $newStatus]);

        return response()->json([
            'success' => true,
            'data' => $evaluationConfig->load(['schoolYear', 'level.section']),
            'message' => $newStatus ? 'Configuration activée' : 'Configuration désactivée'
        ]);
    }
}
