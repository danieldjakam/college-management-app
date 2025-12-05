<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\SubjectGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SubjectGroupController extends Controller
{
    /**
     * Get all subject groups
     */
    public function getAllGroups()
    {
        try {
            $groups = SubjectGroup::active()->ordered()->get();

            return response()->json([
                'success' => true,
                'data' => $groups
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching subject groups: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des groupes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new subject group
     */
    public function createGroup(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|max:50|unique:subject_groups,code',
                'header' => 'required|string|max:255',
                'header_en' => 'nullable|string|max:255',
                'name' => 'required|string|max:255',
                'name_en' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'color' => 'nullable|string|max:20',
                'bg_color' => 'nullable|string|max:20'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Déterminer le prochain ordre
            $maxOrder = SubjectGroup::max('order');
            $nextOrder = $maxOrder ? $maxOrder + 1 : 1;

            $group = SubjectGroup::create([
                'code' => $request->code,
                'header' => $request->header,
                'header_en' => $request->header_en ?? $request->header,
                'name' => $request->name,
                'name_en' => $request->name_en ?? $request->name,
                'description' => $request->description,
                'color' => $request->color ?? '#6b7280',
                'bg_color' => $request->bg_color ?? '#f9fafb',
                'order' => $nextOrder,
                'is_active' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Groupe créé avec succès',
                'data' => $group
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating subject group: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du groupe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a subject group (including code)
     */
    public function updateGroupName(Request $request, $id)
    {
        try {
            $group = SubjectGroup::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'code' => 'nullable|string|max:50|unique:subject_groups,code,' . $id,
                'header' => 'nullable|string|max:255',
                'header_en' => 'nullable|string|max:255',
                'name' => 'required|string|max:255',
                'name_en' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'color' => 'nullable|string|max:20',
                'bg_color' => 'nullable|string|max:20'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Si le code change, mettre à jour toutes les matières associées
            if ($request->has('code') && $request->code !== $group->code) {
                $oldCode = $group->code;

                // Mettre à jour le groupe
                $group->update($request->only(['code', 'header', 'header_en', 'name', 'name_en', 'description', 'color', 'bg_color']));

                // Mettre à jour les matières qui utilisaient l'ancien code
                Subject::where('group', $oldCode)->update(['group' => $request->code]);

                Log::info("Subject group code changed from {$oldCode} to {$request->code}");
            } else {
                // Mise à jour normale sans changement de code
                $group->update($request->only(['header', 'header_en', 'name', 'name_en', 'description', 'color', 'bg_color']));
            }

            return response()->json([
                'success' => true,
                'message' => 'Groupe mis à jour avec succès',
                'data' => $group->fresh()
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
     * Delete a subject group
     */
    public function deleteGroup($id)
    {
        try {
            $group = SubjectGroup::findOrFail($id);

            // Vérifier si des matières sont assignées à ce groupe
            $subjectsCount = Subject::where('group', $group->code)->count();

            if ($subjectsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Impossible de supprimer ce groupe car {$subjectsCount} matière(s) y sont assignées. Veuillez d'abord réassigner ces matières à un autre groupe.",
                    'subjects_count' => $subjectsCount
                ], 400);
            }

            $groupName = $group->name;
            $group->delete();

            return response()->json([
                'success' => true,
                'message' => "Le groupe '{$groupName}' a été supprimé avec succès"
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting subject group: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du groupe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all subjects with their groups
     */
    public function index()
    {
        try {
            // Récupérer les groupes depuis la base de données
            $subjectGroups = SubjectGroup::active()->ordered()->get();

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
            $grouped = [];
            foreach ($subjectGroups as $group) {
                $grouped[$group->code] = $subjects->where('group', $group->code)->values();
            }
            $grouped['uncategorized'] = $subjects->whereNull('group')->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'subjects' => $subjects,
                    'grouped' => $grouped,
                    'groups' => $subjectGroups
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
            // Récupérer tous les codes de groupes existants
            $existingGroupCodes = SubjectGroup::pluck('code')->toArray();

            $validator = Validator::make($request->all(), [
                'group' => ['required', 'string', function ($attribute, $value, $fail) use ($existingGroupCodes) {
                    if (!in_array($value, $existingGroupCodes)) {
                        $fail("Le code de groupe '{$value}' n'existe pas.");
                    }
                }]
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
            Log::info('Bulk update request received', ['data' => $request->all()]);

            // Récupérer tous les codes de groupes existants
            $existingGroupCodes = SubjectGroup::pluck('code')->toArray();
            $validGroupCodes = implode(',', $existingGroupCodes);

            $validator = Validator::make($request->all(), [
                'updates' => 'required|array',
                'updates.*.id' => 'required|exists:subjects,id',
                'updates.*.group' => ['nullable', 'string', function ($attribute, $value, $fail) use ($existingGroupCodes) {
                    // Si la valeur est fournie (non null), elle doit être dans les groupes existants
                    if ($value !== null && !in_array($value, $existingGroupCodes)) {
                        $fail("Le code de groupe '{$value}' n'est pas valide. Codes autorisés: " . implode(', ', $existingGroupCodes));
                    }
                }]
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
