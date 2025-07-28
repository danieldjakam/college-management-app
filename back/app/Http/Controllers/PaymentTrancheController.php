<?php

namespace App\Http\Controllers;

use App\Models\PaymentTranche;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentTrancheController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $tranches = PaymentTranche::active()->ordered()->get();
            
            return response()->json([
                'success' => true,
                'data' => $tranches,
                'message' => 'Tranches récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des tranches',
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
            'name' => 'required|string|max:255|unique:payment_tranches',
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
            // Si aucun ordre n'est spécifié, mettre à la fin
            if (!$request->has('order')) {
                $lastOrder = PaymentTranche::max('order') ?? 0;
                $request->merge(['order' => $lastOrder + 1]);
            }

            $tranche = PaymentTranche::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $tranche,
                'message' => 'Tranche créée avec succès'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la tranche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentTranche $paymentTranche)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $paymentTranche,
                'message' => 'Tranche récupérée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la tranche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentTranche $paymentTranche)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:payment_tranches,name,' . $paymentTranche->id,
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
            $paymentTranche->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $paymentTranche->fresh(),
                'message' => 'Tranche mise à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la tranche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentTranche $paymentTranche)
    {
        try {
            // Vérifier si la tranche est utilisée dans des classes
            if ($paymentTranche->classPaymentAmounts()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cette tranche car elle est utilisée par des classes'
                ], 400);
            }

            $paymentTranche->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tranche supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la tranche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réorganiser l'ordre des tranches
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tranches' => 'required|array',
            'tranches.*.id' => 'required|exists:payment_tranches,id',
            'tranches.*.order' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            foreach ($request->tranches as $trancheData) {
                PaymentTranche::where('id', $trancheData['id'])
                    ->update(['order' => $trancheData['order']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Ordre des tranches mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réorganisation des tranches',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}