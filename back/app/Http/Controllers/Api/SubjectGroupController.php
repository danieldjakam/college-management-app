<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SubjectGroupController extends Controller
{
    /**
     * Get all subjects with their groups
     */
    public function index()
    {
        try {
            $subjects = Subject::select('id', 'name', 'code', 'group')
                ->orderBy('group')
                ->orderBy('name')
                ->get()
                ->map(function ($subject) {
                    return [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'code' => $subject->code,
                        'group' => $subject->group,
                        'group_name' => $subject->group_name
                    ];
                });

            // Grouper par groupe
            $grouped = [
                'A' => $subjects->where('group', 'A')->values(),
                'B' => $subjects->where('group', 'B')->values(),
                'C' => $subjects->where('group', 'C')->values(),
                'D' => $subjects->where('group', 'D')->values(),
                'uncategorized' => $subjects->whereNull('group')->values()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'subjects' => $subjects,
                    'grouped' => $grouped,
                    'groups' => [
                        ['code' => 'A', 'name' => 'MATIÈRES LITTÉRAIRES'],
                        ['code' => 'B', 'name' => 'MATIÈRES SCIENTIFIQUES'],
                        ['code' => 'C', 'name' => 'MATIÈRES PRATIQUES'],
                        ['code' => 'D', 'name' => 'AUTRES MATIÈRES'],
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching subject groups: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des groupes de matières',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update subject group
     */
    public function updateGroup(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'group' => 'required|in:A,B,C,D'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $subject = Subject::findOrFail($id);
            $subject->group = $request->group;
            $subject->save();

            return response()->json([
                'success' => true,
                'message' => 'Groupe de matière mis à jour avec succès',
                'data' => [
                    'subject' => [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'group' => $subject->group,
                        'group_name' => $subject->group_name
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating subject group: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du groupe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update subject groups
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'updates' => 'required|array',
                'updates.*.id' => 'required|exists:subjects,id',
                'updates.*.group' => 'required|in:A,B,C,D'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updated = 0;
            foreach ($request->updates as $update) {
                Subject::where('id', $update['id'])
                    ->update(['group' => $update['group']]);
                $updated++;
            }

            return response()->json([
                'success' => true,
                'message' => "$updated matières mises à jour avec succès",
                'data' => [
                    'updated_count' => $updated
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk updating subject groups: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour en masse',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
