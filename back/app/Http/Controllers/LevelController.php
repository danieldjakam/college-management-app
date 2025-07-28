<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Level::with(['section', 'schoolClasses']);
            
            // Filtrer par section si spécifié
            if ($request->has('section_id')) {
                $query->where('section_id', $request->section_id);
            }
            
            $levels = $query->active()->ordered()->get();
            
            return response()->json([
                'success' => true,
                'data' => $levels,
                'message' => 'Niveaux récupérés avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des niveaux',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'section_id' => 'required|exists:sections,id',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Si aucun ordre n'est spécifié, mettre à la fin pour cette section
            if (!$request->has('order')) {
                $lastOrder = Level::where('section_id', $request->section_id)->max('order') ?? 0;
                $request->merge(['order' => $lastOrder + 1]);
            }

            $level = Level::create($request->all());
            $level->load(['section', 'schoolClasses']);

            return response()->json([
                'success' => true,
                'data' => $level,
                'message' => 'Niveau créé avec succès'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du niveau',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Level $level)
    {
        try {
            $level->load(['section', 'schoolClasses.series', 'schoolClasses.paymentAmounts.paymentTranche']);
            
            return response()->json([
                'success' => true,
                'data' => $level,
                'message' => 'Niveau récupéré avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du niveau',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Level $level)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'section_id' => 'required|exists:sections,id',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $level->update($request->all());
            $level->load(['section', 'schoolClasses']);

            return response()->json([
                'success' => true,
                'data' => $level,
                'message' => 'Niveau mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du niveau',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Level $level)
    {
        try {
            // Vérifier si le niveau a des classes
            if ($level->schoolClasses()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce niveau car il contient des classes'
                ], 400);
            }

            $level->delete();

            return response()->json([
                'success' => true,
                'message' => 'Niveau supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du niveau',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}