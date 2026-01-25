<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SchoolYear;
use App\Models\StudentManualDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentManualDiscountController extends Controller
{
    /**
     * Lister les réductions manuelles avec filtres (section/cycle/série/élève)
     */
    public function index(Request $request)
    {
        $workingYearId = $request->get('school_year_id');
        if (!$workingYearId) {
            $year = SchoolYear::where('is_current', true)->first();
            $workingYearId = $year?->id;
        }

        $query = StudentManualDiscount::query()
            ->where('school_year_id', $workingYearId)
            ->with(['student.classSeries.schoolClass.level.section']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('class_series_id')) {
            $query->whereHas('student', fn($q) => $q->where('class_series_id', $request->class_series_id));
        }

        if ($request->filled('school_class_id')) {
            $query->whereHas('student.classSeries', fn($q) => $q->where('class_id', $request->school_class_id));
        }

        if ($request->filled('level_id')) {
            $query->whereHas('student.classSeries.schoolClass', fn($q) => $q->where('level_id', $request->level_id));
        }

        if ($request->filled('section_id')) {
            $query->whereHas('student.classSeries.schoolClass.level', fn($q) => $q->where('section_id', $request->section_id));
        }

        $items = $query->orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'data' => $items,
            'school_year_id' => $workingYearId,
        ]);
    }

    /**
     * Obtenir la réduction manuelle d'un élève pour une année.
     */
    public function show(Request $request, $studentId)
    {
        $workingYearId = $request->get('school_year_id');
        if (!$workingYearId) {
            $year = SchoolYear::where('is_current', true)->first();
            $workingYearId = $year?->id;
        }

        $discount = StudentManualDiscount::where('student_id', $studentId)
            ->where('school_year_id', $workingYearId)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $discount,
            'school_year_id' => $workingYearId,
        ]);
    }

    /**
     * Créer / mettre à jour (upsert) la réduction manuelle d'un élève.
     */
    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'school_year_id' => 'nullable|integer|exists:school_years,id',
            'amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:1000',
        ]);

        $workingYearId = $validated['school_year_id'] ?? null;
        if (!$workingYearId) {
            $year = SchoolYear::where('is_current', true)->first();
            $workingYearId = $year?->id;
        }

        $discount = StudentManualDiscount::updateOrCreate(
            ['student_id' => $validated['student_id'], 'school_year_id' => $workingYearId],
            [
                'amount' => $validated['amount'],
                'reason' => $validated['reason'] ?? null,
                'created_by' => Auth::id(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Réduction enregistrée',
            'data' => $discount,
        ]);
    }

    public function destroy(Request $request, $studentId)
    {
        $workingYearId = $request->get('school_year_id');
        if (!$workingYearId) {
            $year = SchoolYear::where('is_current', true)->first();
            $workingYearId = $year?->id;
        }

        StudentManualDiscount::where('student_id', $studentId)
            ->where('school_year_id', $workingYearId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Réduction supprimée',
        ]);
    }
}
