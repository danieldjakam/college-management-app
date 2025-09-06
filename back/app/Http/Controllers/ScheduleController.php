<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    /**
     * Get schedules for parent's children
     */
    public function getParentChildrenSchedules(Request $request)
    {
        $parent = Auth::user();
        
        // Get all children of the parent
        $children = Student::whereHas('parents', function($query) use ($parent) {
            $query->where('parent_id', $parent->id);
        })->with('schoolClass')->get();
        
        $schedules = [];
        
        foreach ($children as $child) {
            if ($child->schoolClass) {
                $childSchedules = Schedule::where('class_id', $child->schoolClass->id)
                    ->orderBy('day_of_week')
                    ->orderBy('start_time')
                    ->get()
                    ->map(function($schedule) {
                        return [
                            'id' => $schedule->id,
                            'day' => $schedule->day_of_week,
                            'day_name' => $schedule->day_name,
                            'start_time' => $schedule->start_time->format('H:i'),
                            'end_time' => $schedule->end_time->format('H:i'),
                            'subject' => $schedule->subject,
                            'teacher' => $schedule->teacher_name,
                            'room' => $schedule->room
                        ];
                    });
                
                $schedules[] = [
                    'student' => [
                        'id' => $child->id,
                        'name' => $child->first_name . ' ' . $child->last_name,
                        'class' => $child->schoolClass->name
                    ],
                    'schedule' => $childSchedules->groupBy('day')
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'schedules' => $schedules
        ]);
    }
    
    /**
     * Get all schedules (admin)
     */
    public function index()
    {
        $schedules = Schedule::with('class')
            ->orderBy('class_id')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
            
        return response()->json([
            'success' => true,
            'schedules' => $schedules
        ]);
    }
    
    /**
     * Create new schedule entry
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'day_of_week' => 'required|integer|between:1,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'subject' => 'required|string',
            'teacher_name' => 'nullable|string',
            'room' => 'nullable|string',
            'academic_year' => 'nullable|string'
        ]);
        
        // Check for schedule conflicts
        $conflict = Schedule::where('class_id', $validated['class_id'])
            ->where('day_of_week', $validated['day_of_week'])
            ->where(function($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhere(function($q) use ($validated) {
                        $q->where('start_time', '<=', $validated['start_time'])
                          ->where('end_time', '>=', $validated['end_time']);
                    });
            })
            ->exists();
            
        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'Un cours existe déjà à cet horaire pour cette classe'
            ], 409);
        }
        
        $schedule = Schedule::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Emploi du temps créé avec succès',
            'schedule' => $schedule
        ]);
    }
    
    /**
     * Update schedule entry
     */
    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        
        $validated = $request->validate([
            'class_id' => 'sometimes|exists:school_classes,id',
            'day_of_week' => 'sometimes|integer|between:1,6',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'subject' => 'sometimes|string',
            'teacher_name' => 'nullable|string',
            'room' => 'nullable|string',
            'academic_year' => 'nullable|string'
        ]);
        
        // Check for conflicts if time or day is being changed
        if (isset($validated['day_of_week']) || isset($validated['start_time']) || isset($validated['end_time'])) {
            $day = $validated['day_of_week'] ?? $schedule->day_of_week;
            $start = $validated['start_time'] ?? $schedule->start_time;
            $end = $validated['end_time'] ?? $schedule->end_time;
            $classId = $validated['class_id'] ?? $schedule->class_id;
            
            $conflict = Schedule::where('class_id', $classId)
                ->where('day_of_week', $day)
                ->where('id', '!=', $id)
                ->where(function($query) use ($start, $end) {
                    $query->whereBetween('start_time', [$start, $end])
                        ->orWhereBetween('end_time', [$start, $end])
                        ->orWhere(function($q) use ($start, $end) {
                            $q->where('start_time', '<=', $start)
                              ->where('end_time', '>=', $end);
                        });
                })
                ->exists();
                
            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un cours existe déjà à cet horaire pour cette classe'
                ], 409);
            }
        }
        
        $schedule->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Emploi du temps mis à jour avec succès',
            'schedule' => $schedule
        ]);
    }
    
    /**
     * Delete schedule entry
     */
    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Emploi du temps supprimé avec succès'
        ]);
    }
    
    /**
     * Bulk create schedules (for importing)
     */
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.class_id' => 'required|exists:school_classes,id',
            'schedules.*.day_of_week' => 'required|integer|between:1,6',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i',
            'schedules.*.subject' => 'required|string',
            'schedules.*.teacher_name' => 'nullable|string',
            'schedules.*.room' => 'nullable|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            $created = [];
            foreach ($validated['schedules'] as $scheduleData) {
                $created[] = Schedule::create($scheduleData);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => count($created) . ' emplois du temps créés avec succès',
                'schedules' => $created
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création des emplois du temps: ' . $e->getMessage()
            ], 500);
        }
    }
}