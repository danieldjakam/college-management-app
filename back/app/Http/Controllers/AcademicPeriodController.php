<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\AcademicSystemConfig;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicPeriodController extends Controller
{
    public function getConfig()
    {
        $config = AcademicSystemConfig::getActiveConfig();
        
        return response()->json([
            'success' => true,
            'data' => $config,
            'default_periods' => AcademicSystemConfig::getDefaultPercentages()
        ]);
    }

    public function updateConfig(Request $request)
    {
        $request->validate([
            'type' => 'required|in:semester,trimester',
            'periods_count' => 'required|integer|min:2|max:6',
            'description' => 'nullable|string'
        ]);

        AcademicSystemConfig::query()->update(['is_active' => false]);

        $config = AcademicSystemConfig::create([
            'type' => $request->type,
            'periods_count' => $request->periods_count,
            'is_active' => true,
            'description' => $request->description
        ]);

        return response()->json([
            'success' => true,
            'data' => $config,
            'message' => 'Configuration mise à jour avec succès'
        ]);
    }

    public function index(Request $request)
    {
        $yearId = $request->get('school_year_id');
        
        $query = AcademicPeriod::with('schoolYear')->ordered();
        
        if ($yearId) {
            $query->forYear($yearId);
        }
        
        $periods = $query->get();
        $totalPercentage = $periods->sum('percentage');
        
        return response()->json([
            'success' => true,
            'data' => $periods,
            'validation' => [
                'total_percentage' => $totalPercentage,
                'is_valid' => $totalPercentage == 100,
                'difference' => 100 - $totalPercentage
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
            'order' => 'required|integer|min:1',
            'school_year_id' => 'required|exists:school_years,id',
            'description' => 'nullable|string'
        ]);

        $existingTotal = AcademicPeriod::getTotalPercentageForYear($request->school_year_id);
        $newTotal = $existingTotal + $request->percentage;

        if ($newTotal > 100) {
            return response()->json([
                'success' => false,
                'message' => "Le total des pourcentages dépasse 100%. Total actuel: {$existingTotal}%, ajout demandé: {$request->percentage}%"
            ], 422);
        }

        $period = AcademicPeriod::create($request->all());

        $validation = [
            'total_percentage' => $newTotal,
            'is_valid' => $newTotal == 100,
            'difference' => 100 - $newTotal
        ];

        return response()->json([
            'success' => true,
            'data' => $period->load('schoolYear'),
            'validation' => $validation,
            'message' => 'Période créée avec succès'
        ], 201);
    }

    public function show(AcademicPeriod $academicPeriod)
    {
        return response()->json([
            'success' => true,
            'data' => $academicPeriod->load('schoolYear')
        ]);
    }

    public function update(Request $request, AcademicPeriod $academicPeriod)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
            'order' => 'required|integer|min:1',
            'description' => 'nullable|string'
        ]);

        $existingTotal = AcademicPeriod::getTotalPercentageForYear($academicPeriod->school_year_id, $academicPeriod->id);
        $newTotal = $existingTotal + $request->percentage;

        if ($newTotal > 100) {
            return response()->json([
                'success' => false,
                'message' => "Le total des pourcentages dépasse 100%. Total actuel: {$existingTotal}%, modification demandée: {$request->percentage}%"
            ], 422);
        }

        $academicPeriod->update($request->all());

        $validation = [
            'total_percentage' => $newTotal,
            'is_valid' => $newTotal == 100,
            'difference' => 100 - $newTotal
        ];

        return response()->json([
            'success' => true,
            'data' => $academicPeriod->load('schoolYear'),
            'validation' => $validation,
            'message' => 'Période mise à jour avec succès'
        ]);
    }

    public function destroy(AcademicPeriod $academicPeriod)
    {
        $academicPeriod->delete();

        return response()->json([
            'success' => true,
            'message' => 'Période supprimée avec succès'
        ]);
    }

    public function validateYear(Request $request)
    {
        $request->validate([
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        $total = AcademicPeriod::getTotalPercentageForYear($request->school_year_id);
        
        return response()->json([
            'success' => true,
            'validation' => [
                'total_percentage' => $total,
                'is_valid' => $total == 100,
                'difference' => 100 - $total
            ]
        ]);
    }
}
