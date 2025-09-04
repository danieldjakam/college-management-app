<?php

namespace App\Http\Controllers;

use App\Models\GeolocationZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GeolocationZoneController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $zones = GeolocationZone::with('creator:id,name')->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $zones
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des zones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:geolocation_zones,name',
                'description' => 'nullable|string|max:500',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'radius' => 'required|integer|min:1|max:10000',
                'enabled' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $zone = GeolocationZone::create([
                'name' => $request->name,
                'description' => $request->description,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'radius' => $request->radius,
                'enabled' => $request->boolean('enabled', true),
                'created_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Zone créée avec succès',
                'data' => $zone->load('creator:id,name')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la zone',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(GeolocationZone $zone)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $zone->load('creator:id,name')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Zone non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GeolocationZone $zone)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:geolocation_zones,name,' . $zone->id,
                'description' => 'nullable|string|max:500',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'radius' => 'required|integer|min:1|max:10000',
                'enabled' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $zone->update([
                'name' => $request->name,
                'description' => $request->description,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'radius' => $request->radius,
                'enabled' => $request->boolean('enabled', true)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Zone mise à jour avec succès',
                'data' => $zone->load('creator:id,name')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la zone',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GeolocationZone $zone)
    {
        try {
            $zone->delete();

            return response()->json([
                'success' => true,
                'message' => 'Zone supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la zone',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the enabled status of a zone
     */
    public function toggleStatus(GeolocationZone $zone)
    {
        try {
            $zone->update(['enabled' => !$zone->enabled]);

            return response()->json([
                'success' => true,
                'message' => 'Statut de la zone mis à jour',
                'data' => $zone
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all enabled zones for position validation
     */
    public function getEnabledZones()
    {
        try {
            $zones = GeolocationZone::enabled()->get(['id', 'name', 'latitude', 'longitude', 'radius']);

            return response()->json([
                'success' => true,
                'data' => $zones
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des zones actives',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate if a position is within authorized zones
     */
    public function validatePosition(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Coordonnées invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $latitude = $request->latitude;
            $longitude = $request->longitude;

            // Récupérer toutes les zones actives
            $zones = GeolocationZone::enabled()->get();
            
            $validationResult = [
                'isAuthorized' => false,
                'zones' => [],
                'closestZone' => null
            ];

            $minDistance = PHP_FLOAT_MAX;
            $closestZone = null;

            foreach ($zones as $zone) {
                $distance = GeolocationZone::calculateDistance(
                    $zone->latitude,
                    $zone->longitude,
                    $latitude,
                    $longitude
                );

                $isInZone = $distance <= $zone->radius;

                $validationResult['zones'][] = [
                    'id' => $zone->id,
                    'zoneName' => $zone->name,
                    'distance' => round($distance, 2),
                    'radius' => $zone->radius,
                    'isInZone' => $isInZone
                ];

                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $closestZone = [
                        'id' => $zone->id,
                        'zoneName' => $zone->name,
                        'distance' => round($distance, 2),
                        'radius' => $zone->radius,
                        'isInZone' => $isInZone
                    ];
                }

                if ($isInZone) {
                    $validationResult['isAuthorized'] = true;
                }
            }

            $validationResult['closestZone'] = $closestZone;

            return response()->json([
                'success' => true,
                'data' => $validationResult,
                'message' => $validationResult['isAuthorized'] 
                    ? 'Position autorisée' 
                    : 'Position non autorisée'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de position',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
