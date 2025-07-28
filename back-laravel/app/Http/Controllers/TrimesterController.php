<?php

namespace App\Http\Controllers;

use App\Models\Trimester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrimesterController extends Controller
{
    /**
     * Get all trimesters
     */
    public function getAllTrimesters()
    {
        $trimesters = Trimester::with(['sequences'])
                              ->orderBy('start_date')
                              ->get();
        return response()->json($trimesters);
    }

    /**
     * Get current active trimester
     */
    public function getCurrentTrimester()
    {
        $trimester = Trimester::where('is_active', true)->first();
        
        if (!$trimester) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun trimestre actif trouvé'
            ], 404);
        }

        return response()->json($trimester);
    }

    /**
     * Get one trimester
     */
    public function getOneTrimester($id)
    {
        $trimester = Trimester::with(['sequences'])->find($id);

        if (!$trimester) {
            return response()->json([
                'success' => false,
                'message' => 'Trimestre non trouvé'
            ], 404);
        }

        return response()->json($trimester);
    }

    /**
     * Add new trimester
     */
    public function addTrimester(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'school_year' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // If this trimester should be active, deactivate others
        if ($request->is_active) {
            Trimester::where('is_active', true)->update(['is_active' => false]);
        }

        $trimester = Trimester::create([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'school_year' => $request->school_year ?? '2024-2025',
            'is_active' => $request->is_active ?? false,
            'school_id' => $request->school_id ?? 'GSBPL_001',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trimestre ajouté avec succès',
            'data' => $trimester
        ], 201);
    }

    /**
     * Update trimester
     */
    public function updateTrimester(Request $request, $id)
    {
        $trimester = Trimester::find($id);

        if (!$trimester) {
            return response()->json([
                'success' => false,
                'message' => 'Trimestre non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|min:2|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'school_year' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // If this trimester should be active, deactivate others
        if ($request->is_active) {
            Trimester::where('id', '!=', $id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
        }

        $updateData = $request->only([
            'name', 'start_date', 'end_date', 'school_year', 'is_active'
        ]);

        $trimester->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Trimestre mis à jour avec succès',
            'data' => $trimester
        ]);
    }

    /**
     * Delete trimester
     */
    public function deleteTrimester($id)
    {
        $trimester = Trimester::find($id);

        if (!$trimester) {
            return response()->json([
                'success' => false,
                'message' => 'Trimestre non trouvé'
            ], 404);
        }

        // Check if trimester has sequences
        if ($trimester->sequences()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un trimestre qui contient des séquences'
            ], 400);
        }

        $trimester->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trimestre supprimé avec succès'
        ]);
    }

    /**
     * Activate trimester
     */
    public function activateTrimester($id)
    {
        $trimester = Trimester::find($id);

        if (!$trimester) {
            return response()->json([
                'success' => false,
                'message' => 'Trimestre non trouvé'
            ], 404);
        }

        // Deactivate all other trimesters
        Trimester::where('id', '!=', $id)->update(['is_active' => false]);
        
        // Activate this trimester
        $trimester->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Trimestre activé avec succès',
            'data' => $trimester
        ]);
    }
}