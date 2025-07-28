<?php

namespace App\Http\Controllers;

use App\Models\Sequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SequenceController extends Controller
{
    /**
     * Get all sequences
     */
    public function getAllSequences()
    {
        $sequences = Sequence::orderBy('start_date')->get();
        return response()->json($sequences);
    }

    /**
     * Get current active sequence
     */
    public function getCurrentSequence()
    {
        $sequence = Sequence::where('is_active', true)->first();
        
        if (!$sequence) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune séquence active trouvée'
            ], 404);
        }

        return response()->json($sequence);
    }

    /**
     * Get one sequence
     */
    public function getOneSequence($id)
    {
        $sequence = Sequence::find($id);

        if (!$sequence) {
            return response()->json([
                'success' => false,
                'message' => 'Séquence non trouvée'
            ], 404);
        }

        return response()->json($sequence);
    }

    /**
     * Add new sequence
     */
    public function addSequence(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'trimester_id' => 'sometimes|string|exists:trimesters,id',
            'school_year' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // If this sequence should be active, deactivate others
        if ($request->is_active) {
            Sequence::where('is_active', true)->update(['is_active' => false]);
        }

        $sequence = Sequence::create([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'trimester_id' => $request->trimester_id,
            'school_year' => $request->school_year ?? '2024-2025',
            'is_active' => $request->is_active ?? false,
            'school_id' => $request->school_id ?? 'GSBPL_001',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Séquence ajoutée avec succès',
            'data' => $sequence
        ], 201);
    }

    /**
     * Update sequence
     */
    public function updateSequence(Request $request, $id)
    {
        $sequence = Sequence::find($id);

        if (!$sequence) {
            return response()->json([
                'success' => false,
                'message' => 'Séquence non trouvée'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|min:2|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'trimester_id' => 'sometimes|string|exists:trimesters,id',
            'school_year' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // If this sequence should be active, deactivate others
        if ($request->is_active) {
            Sequence::where('id', '!=', $id)
                   ->where('is_active', true)
                   ->update(['is_active' => false]);
        }

        $updateData = $request->only([
            'name', 'start_date', 'end_date', 'trimester_id', 
            'school_year', 'is_active'
        ]);

        $sequence->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Séquence mise à jour avec succès',
            'data' => $sequence
        ]);
    }

    /**
     * Delete sequence
     */
    public function deleteSequence($id)
    {
        $sequence = Sequence::find($id);

        if (!$sequence) {
            return response()->json([
                'success' => false,
                'message' => 'Séquence non trouvée'
            ], 404);
        }

        // Check if sequence has notes
        if ($sequence->notes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une séquence qui contient des notes'
            ], 400);
        }

        $sequence->delete();

        return response()->json([
            'success' => true,
            'message' => 'Séquence supprimée avec succès'
        ]);
    }

    /**
     * Activate sequence
     */
    public function activateSequence($id)
    {
        $sequence = Sequence::find($id);

        if (!$sequence) {
            return response()->json([
                'success' => false,
                'message' => 'Séquence non trouvée'
            ], 404);
        }

        // Deactivate all other sequences
        Sequence::where('id', '!=', $id)->update(['is_active' => false]);
        
        // Activate this sequence
        $sequence->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Séquence activée avec succès',
            'data' => $sequence
        ]);
    }
}