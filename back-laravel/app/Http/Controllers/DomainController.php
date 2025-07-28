<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DomainController extends Controller
{
    /**
     * Get all domains
     */
    public function getAllDomains()
    {
        $domains = Domain::with(['subjects'])
                         ->orderBy('name')
                         ->get();
        return response()->json($domains);
    }

    /**
     * Get one domain
     */
    public function getOneDomain($id)
    {
        $domain = Domain::with(['subjects'])->find($id);

        if (!$domain) {
            return response()->json([
                'success' => false,
                'message' => 'Domaine non trouvé'
            ], 404);
        }

        return response()->json($domain);
    }

    /**
     * Add new domain
     */
    public function addDomain(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255|unique:domains,name',
            'description' => 'sometimes|string|max:500',
            'coefficient' => 'sometimes|numeric|min:0|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $domain = Domain::create([
            'name' => $request->name,
            'description' => $request->description,
            'coefficient' => $request->coefficient ?? 1,
            'school_id' => $request->school_id ?? 'GSBPL_001',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Domaine ajouté avec succès',
            'data' => $domain
        ], 201);
    }

    /**
     * Update domain
     */
    public function updateDomain(Request $request, $id)
    {
        $domain = Domain::find($id);

        if (!$domain) {
            return response()->json([
                'success' => false,
                'message' => 'Domaine non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|min:2|max:255|unique:domains,name,' . $id,
            'description' => 'sometimes|string|max:500',
            'coefficient' => 'sometimes|numeric|min:0|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $updateData = $request->only([
            'name', 'description', 'coefficient'
        ]);

        $domain->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Domaine mis à jour avec succès',
            'data' => $domain
        ]);
    }

    /**
     * Delete domain
     */
    public function deleteDomain($id)
    {
        $domain = Domain::find($id);

        if (!$domain) {
            return response()->json([
                'success' => false,
                'message' => 'Domaine non trouvé'
            ], 404);
        }

        // Check if domain has subjects
        if ($domain->subjects()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un domaine qui contient des matières'
            ], 400);
        }

        $domain->delete();

        return response()->json([
            'success' => true,
            'message' => 'Domaine supprimé avec succès'
        ]);
    }
}