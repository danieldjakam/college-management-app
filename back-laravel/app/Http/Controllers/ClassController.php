<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClassController extends Controller
{
    /**
     * Get all classes
     */
    public function getAllClass()
    {
        $classes = SchoolClass::with(['section', 'teacher'])
                             ->withCount('students')
                             ->get();
        return response()->json($classes);
    }

    /**
     * Get all classes by section
     */
    public function getAllOClass($section_id)
    {
        $classes = SchoolClass::where('section', $section_id)
                             ->with(['section', 'teacher'])
                             ->withCount('students')
                             ->get();
        return response()->json($classes);
    }

    /**
     * Get special class info
     */
    public function getSpecialClass($id)
    {
        $class = SchoolClass::with(['section', 'teacher', 'students'])
                           ->withCount('students')
                           ->find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Classe non trouvée'
            ], 404);
        }

        return response()->json($class);
    }

    /**
     * Get one class
     */
    public function getOneClass($id)
    {
        $class = SchoolClass::with(['section', 'teacher'])
                           ->withCount('students')
                           ->find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Classe non trouvée'
            ], 404);
        }

        return response()->json($class);
    }

    /**
     * Add new class
     */
    public function addClass(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'level' => 'required|integer|min:1',
            'section' => 'required|integer|exists:sections,id',
            'school_id' => 'sometimes|string',
            'school_year' => 'sometimes|string',
            'teacherId' => 'sometimes|string|exists:teachers,id',
            'inscriptions_olds_students' => 'sometimes|numeric|min:0',
            'inscriptions_news_students' => 'sometimes|numeric|min:0',
            'first_tranch_news_students' => 'sometimes|numeric|min:0',
            'first_tranch_olds_students' => 'sometimes|numeric|min:0',
            'second_tranch_news_students' => 'sometimes|numeric|min:0',
            'second_tranch_olds_students' => 'sometimes|numeric|min:0',
            'third_tranch_news_students' => 'sometimes|numeric|min:0',
            'third_tranch_olds_students' => 'sometimes|numeric|min:0',
            'graduation' => 'sometimes|numeric|min:0',
            'first_date' => 'sometimes|integer',
            'last_date' => 'sometimes|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $class = SchoolClass::create([
            'id' => $this->generateClassId($request->name),
            'name' => $request->name,
            'level' => $request->level,
            'section' => $request->section,
            'school_id' => $request->school_id ?? 'GSBPL_001',
            'school_year' => $request->school_year ?? '2024-2025',
            'teacherId' => $request->teacherId,
            'inscriptions_olds_students' => $request->inscriptions_olds_students ?? 0,
            'inscriptions_news_students' => $request->inscriptions_news_students ?? 0,
            'first_tranch_news_students' => $request->first_tranch_news_students ?? 0,
            'first_tranch_olds_students' => $request->first_tranch_olds_students ?? 0,
            'second_tranch_news_students' => $request->second_tranch_news_students ?? 0,
            'second_tranch_olds_students' => $request->second_tranch_olds_students ?? 0,
            'third_tranch_news_students' => $request->third_tranch_news_students ?? 0,
            'third_tranch_olds_students' => $request->third_tranch_olds_students ?? 0,
            'graduation' => $request->graduation ?? 0,
            'first_date' => $request->first_date,
            'last_date' => $request->last_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Classe créée avec succès',
            'data' => $class->load(['section', 'teacher'])
        ], 201);
    }

    /**
     * Update class
     */
    public function updateClass(Request $request, $id)
    {
        $class = SchoolClass::find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Classe non trouvée'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|min:3|max:255',
            'level' => 'sometimes|integer|min:1',
            'section' => 'sometimes|integer|exists:sections,id',
            'teacherId' => 'sometimes|string|exists:teachers,id',
            'inscriptions_olds_students' => 'sometimes|numeric|min:0',
            'inscriptions_news_students' => 'sometimes|numeric|min:0',
            'first_tranch_news_students' => 'sometimes|numeric|min:0',
            'first_tranch_olds_students' => 'sometimes|numeric|min:0',
            'second_tranch_news_students' => 'sometimes|numeric|min:0',
            'second_tranch_olds_students' => 'sometimes|numeric|min:0',
            'third_tranch_news_students' => 'sometimes|numeric|min:0',
            'third_tranch_olds_students' => 'sometimes|numeric|min:0',
            'graduation' => 'sometimes|numeric|min:0',
            'first_date' => 'sometimes|integer',
            'last_date' => 'sometimes|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $updateData = $request->only([
            'name', 'level', 'section', 'teacherId',
            'inscriptions_olds_students', 'inscriptions_news_students',
            'first_tranch_news_students', 'first_tranch_olds_students',
            'second_tranch_news_students', 'second_tranch_olds_students',
            'third_tranch_news_students', 'third_tranch_olds_students',
            'graduation', 'first_date', 'last_date'
        ]);

        $class->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Classe mise à jour avec succès',
            'data' => $class->load(['section', 'teacher'])
        ]);
    }

    /**
     * Delete class
     */
    public function deleteClass($id)
    {
        $class = SchoolClass::find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Classe non trouvée'
            ], 404);
        }

        // Check if class has students
        if ($class->students()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une classe qui contient des étudiants'
            ], 400);
        }

        $class->delete();

        return response()->json([
            'success' => true,
            'message' => 'Classe supprimée avec succès'
        ]);
    }

    /**
     * Generate unique class ID
     */
    private function generateClassId($name)
    {
        return 'CLASS_' . strtoupper(str_replace(' ', '_', $name)) . '_' . time();
    }
}