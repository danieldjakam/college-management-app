<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    /**
     * Get all subjects
     */
    public function getAllSubjects()
    {
        $subjects = Subject::with(['domain'])
                          ->orderBy('name')
                          ->get();
        return response()->json($subjects);
    }

    /**
     * Get subjects by domain
     */
    public function getSubjectsByDomain($domainId)
    {
        $subjects = Subject::where('domain_id', $domainId)
                          ->with(['domain'])
                          ->orderBy('name')
                          ->get();
        return response()->json($subjects);
    }

    /**
     * Get one subject
     */
    public function getOneSubject($id)
    {
        $subject = Subject::with(['domain'])->find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Matière non trouvée'
            ], 404);
        }

        return response()->json($subject);
    }

    /**
     * Add new subject
     */
    public function addSubject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255',
            'domain_id' => 'required|string|exists:domains,id',
            'coefficient' => 'sometimes|numeric|min:0|max:10',
            'description' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $subject = Subject::create([
            'name' => $request->name,
            'domain_id' => $request->domain_id,
            'coefficient' => $request->coefficient ?? 1,
            'description' => $request->description,
            'school_id' => $request->school_id ?? 'GSBPL_001',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Matière ajoutée avec succès',
            'data' => $subject->load(['domain'])
        ], 201);
    }

    /**
     * Update subject
     */
    public function updateSubject(Request $request, $id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Matière non trouvée'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|min:2|max:255',
            'domain_id' => 'sometimes|string|exists:domains,id',
            'coefficient' => 'sometimes|numeric|min:0|max:10',
            'description' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $updateData = $request->only([
            'name', 'domain_id', 'coefficient', 'description'
        ]);

        $subject->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Matière mise à jour avec succès',
            'data' => $subject->load(['domain'])
        ]);
    }

    /**
     * Delete subject
     */
    public function deleteSubject($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Matière non trouvée'
            ], 404);
        }

        // Check if subject has notes
        if ($subject->notes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une matière qui a des notes enregistrées'
            ], 400);
        }

        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Matière supprimée avec succès'
        ]);
    }
}