<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\ClassSeries;
use App\Models\ClassPaymentAmount;
use App\Models\PaymentTranche;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SchoolClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = SchoolClass::with(['level.section', 'series', 'paymentAmounts.paymentTranche']);
            
            // Filtrer par niveau si spécifié
            if ($request->has('level_id')) {
                $query->where('level_id', $request->level_id);
            }
            
            // Filtrer par section si spécifié
            if ($request->has('section_id')) {
                $query->whereHas('level', function($q) use ($request) {
                    $q->where('section_id', $request->section_id);
                });
            }
            
            $classes = $query->active()->get();
            
            return response()->json([
                'success' => true,
                'data' => $classes,
                'message' => 'Classes récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des classes',
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
            'level_id' => 'required|exists:levels,id',
            'description' => 'nullable|string',
            'series' => 'required|array|min:1',
            'series.*.name' => 'required|string|max:10',
            'series.*.capacity' => 'nullable|integer|min:1|max:100',
            'payment_amounts' => 'nullable|array',
            'payment_amounts.*.payment_tranche_id' => 'required|exists:payment_tranches,id',
            'payment_amounts.*.amount_new_students' => 'required|numeric|min:0',
            'payment_amounts.*.amount_old_students' => 'required|numeric|min:0',
            'payment_amounts.*.is_required' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Créer la classe
            $schoolClass = SchoolClass::create([
                'name' => $request->name,
                'level_id' => $request->level_id,
                'description' => $request->description
            ]);

            // Créer les séries
            foreach ($request->series as $seriesData) {
                ClassSeries::create([
                    'class_id' => $schoolClass->id,
                    'name' => $seriesData['name'],
                    'capacity' => $seriesData['capacity'] ?? 50
                ]);
            }

            // Créer les montants de paiement si fournis
            if ($request->has('payment_amounts')) {
                foreach ($request->payment_amounts as $paymentData) {
                    ClassPaymentAmount::create([
                        'class_id' => $schoolClass->id,
                        'payment_tranche_id' => $paymentData['payment_tranche_id'],
                        'amount_new_students' => $paymentData['amount_new_students'],
                        'amount_old_students' => $paymentData['amount_old_students'],
                        'is_required' => $paymentData['is_required'] ?? true
                    ]);
                }
            }

            DB::commit();

            // Recharger avec les relations
            $schoolClass->load([
                'level.section', 
                'series', 
                'paymentAmounts.paymentTranche'
            ]);

            return response()->json([
                'success' => true,
                'data' => $schoolClass,
                'message' => 'Classe créée avec succès'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la classe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SchoolClass $schoolClass)
    {
        try {
            $schoolClass->load([
                'level.section',
                'series.students',
                'paymentAmounts.paymentTranche'
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $schoolClass,
                'message' => 'Classe récupérée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la classe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SchoolClass $schoolClass)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'level_id' => 'required|exists:levels,id',
            'description' => 'nullable|string',
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
            $schoolClass->update($request->all());
            $schoolClass->load(['level.section', 'series', 'paymentAmounts.paymentTranche']);

            return response()->json([
                'success' => true,
                'data' => $schoolClass,
                'message' => 'Classe mise à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la classe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SchoolClass $schoolClass)
    {
        try {
            // Vérifier s'il y a des étudiants dans les séries de cette classe
            if ($schoolClass->students()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cette classe car elle contient des étudiants'
                ], 400);
            }

            $schoolClass->delete();

            return response()->json([
                'success' => true,
                'message' => 'Classe supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la classe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Configurer les montants de paiement pour une classe
     */
    public function configurePayments(Request $request, SchoolClass $schoolClass)
    {
        $validator = Validator::make($request->all(), [
            'payment_amounts' => 'required|array',
            'payment_amounts.*.payment_tranche_id' => 'required|exists:payment_tranches,id',
            'payment_amounts.*.amount_new_students' => 'required|numeric|min:0',
            'payment_amounts.*.amount_old_students' => 'required|numeric|min:0',
            'payment_amounts.*.is_required' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Supprimer les anciens montants
            $schoolClass->paymentAmounts()->delete();

            // Créer les nouveaux montants
            foreach ($request->payment_amounts as $paymentData) {
                ClassPaymentAmount::create([
                    'class_id' => $schoolClass->id,
                    'payment_tranche_id' => $paymentData['payment_tranche_id'],
                    'amount_new_students' => $paymentData['amount_new_students'],
                    'amount_old_students' => $paymentData['amount_old_students'],
                    'is_required' => $paymentData['is_required'] ?? true
                ]);
            }

            DB::commit();

            // Recharger avec les relations
            $schoolClass->load(['paymentAmounts.paymentTranche']);

            return response()->json([
                'success' => true,
                'data' => $schoolClass,
                'message' => 'Montants de paiement configurés avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la configuration des paiements',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}