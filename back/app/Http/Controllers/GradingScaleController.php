<?php

namespace App\Http\Controllers;

use App\Models\GradingScale;
use App\Models\SchoolYear;
use App\Models\Level;
use Illuminate\Http\Request;

class GradingScaleController extends Controller
{
    public function index(Request $request)
    {
        $yearId = $request->get('school_year_id');
        $levelId = $request->get('level_id');
        
        $query = GradingScale::with(['schoolYear', 'level.section']);
        
        if ($yearId) {
            $query->forYear($yearId);
        }
        
        if ($levelId) {
            $query->forLevel($levelId);
        }
        
        $gradingScales = $query->ordered()->get();
        
        return response()->json([
            'success' => true,
            'data' => $gradingScales
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'level_id' => 'nullable|exists:levels,id',
            'grade_code' => 'required|string|max:5',
            'grade_label' => 'required|string|max:50',
            'min_score' => 'required|numeric|min:0|max:20',
            'max_score' => 'required|numeric|min:0|max:20|gte:min_score',
            'appreciation' => 'required|string',
            'color_code' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'passing_threshold' => 'nullable|numeric|min:0|max:20',
            'is_passing_grade' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'integer|min:0'
        ]);

        // Vérifier les chevauchements de notes
        $overlapping = GradingScale::forYear($request->school_year_id)
            ->when($request->level_id, function($q) use ($request) {
                return $q->forLevel($request->level_id);
            }, function($q) {
                return $q->global();
            })
            ->where(function($q) use ($request) {
                $q->whereBetween('min_score', [$request->min_score, $request->max_score])
                  ->orWhereBetween('max_score', [$request->min_score, $request->max_score])
                  ->orWhere(function($sq) use ($request) {
                      $sq->where('min_score', '<=', $request->min_score)
                        ->where('max_score', '>=', $request->max_score);
                  });
            })
            ->exists();

        if ($overlapping) {
            return response()->json([
                'success' => false,
                'message' => 'Les intervalles de notes ne peuvent pas se chevaucher avec une échelle existante'
            ], 422);
        }

        $gradingScale = GradingScale::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $gradingScale->load(['schoolYear', 'level.section']),
            'message' => 'Barème de notation créé avec succès'
        ], 201);
    }

    public function show(GradingScale $gradingScale)
    {
        return response()->json([
            'success' => true,
            'data' => $gradingScale->load(['schoolYear', 'level.section'])
        ]);
    }

    public function update(Request $request, GradingScale $gradingScale)
    {
        $request->validate([
            'grade_code' => 'required|string|max:5',
            'grade_label' => 'required|string|max:50',
            'min_score' => 'required|numeric|min:0|max:20',
            'max_score' => 'required|numeric|min:0|max:20|gte:min_score',
            'appreciation' => 'required|string',
            'color_code' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'passing_threshold' => 'nullable|numeric|min:0|max:20',
            'is_passing_grade' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'integer|min:0'
        ]);

        // Vérifier les chevauchements (excluant l'échelle courante)
        $overlapping = GradingScale::forYear($gradingScale->school_year_id)
            ->when($gradingScale->level_id, function($q) use ($gradingScale) {
                return $q->forLevel($gradingScale->level_id);
            }, function($q) {
                return $q->global();
            })
            ->where('id', '!=', $gradingScale->id)
            ->where(function($q) use ($request) {
                $q->whereBetween('min_score', [$request->min_score, $request->max_score])
                  ->orWhereBetween('max_score', [$request->min_score, $request->max_score])
                  ->orWhere(function($sq) use ($request) {
                      $sq->where('min_score', '<=', $request->min_score)
                        ->where('max_score', '>=', $request->max_score);
                  });
            })
            ->exists();

        if ($overlapping) {
            return response()->json([
                'success' => false,
                'message' => 'Les intervalles de notes ne peuvent pas se chevaucher avec une échelle existante'
            ], 422);
        }

        $gradingScale->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $gradingScale->load(['schoolYear', 'level.section']),
            'message' => 'Barème de notation mis à jour avec succès'
        ]);
    }

    public function destroy(GradingScale $gradingScale)
    {
        $gradingScale->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barème de notation supprimé avec succès'
        ]);
    }

    public function createDefaultScale(Request $request)
    {
        $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'level_id' => 'nullable|exists:levels,id'
        ]);

        // Vérifier s'il existe déjà une échelle pour ce niveau/année
        $existing = GradingScale::forYear($request->school_year_id)
            ->when($request->level_id, function($q) use ($request) {
                return $q->forLevel($request->level_id);
            }, function($q) {
                return $q->global();
            })
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Une échelle de notation existe déjà pour ce niveau/année'
            ], 422);
        }

        GradingScale::createDefaultScale($request->school_year_id, $request->level_id);

        return response()->json([
            'success' => true,
            'message' => 'Échelle de notation par défaut créée avec succès'
        ]);
    }

    public function getGradeForScore(Request $request)
    {
        $request->validate([
            'score' => 'required|numeric|min:0|max:20',
            'school_year_id' => 'required|exists:school_years,id',
            'level_id' => 'nullable|exists:levels,id'
        ]);

        $grade = GradingScale::getGradeForScore(
            $request->score, 
            $request->school_year_id, 
            $request->level_id
        );

        if (!$grade) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun barème trouvé pour cette note'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $grade
        ]);
    }
}
