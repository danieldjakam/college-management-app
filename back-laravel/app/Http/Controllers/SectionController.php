<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SectionController extends Controller
{
    /**
     * Get all sections
     */
    public function getAllSection()
    {
        $sections = Section::withCount('classes as total_class')->get();
        return response()->json($sections);
    }

    /**
     * Get one section
     */
    public function getOneSection($id)
    {
        $section = Section::with(['classes', 'subjects', 'domains', 'competences'])
                         ->withCount('classes as total_class')
                         ->find($id);

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section non trouvée'
            ], 404);
        }

        return response()->json($section);
    }

    /**
     * Get number of classes in a section
     */
    public function getNberOfClass($id)
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section non trouvée'
            ], 404);
        }

        $classCount = $section->classes()->count();

        return response()->json([
            'section_id' => $id,
            'total_classes' => $classCount
        ]);
    }

    /**
     * Add new section
     */
    public function addSection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'type' => 'required|string|max:100',
            'school_year' => 'sometimes|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $section = Section::create([
            'name' => $request->name,
            'type' => $request->type,
            'school_year' => $request->school_year ?? '2024-2025'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section créée avec succès',
            'data' => $section
        ], 201);
    }

    /**
     * Update section
     */
    public function updateSection(Request $request, $id)
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section non trouvée'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|min:3|max:255',
            'type' => 'sometimes|string|max:100',
            'school_year' => 'sometimes|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $section->update($request->only(['name', 'type', 'school_year']));

        return response()->json([
            'success' => true,
            'message' => 'Section mise à jour avec succès',
            'data' => $section
        ]);
    }

    /**
     * Delete section
     */
    public function deleteSection($id)
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section non trouvée'
            ], 404);
        }

        // Check if section has classes
        if ($section->classes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une section qui contient des classes'
            ], 400);
        }

        $section->delete();

        return response()->json([
            'success' => true,
            'message' => 'Section supprimée avec succès'
        ]);
    }
}