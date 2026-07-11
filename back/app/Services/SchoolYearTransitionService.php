<?php

namespace App\Services;

use App\Models\SchoolYear;
use App\Models\ClassSeries;
use App\Models\ClassSeriesSubject;
use App\Models\Trimester;
use App\Models\Sequence;
use App\Models\TeacherAssignment;
use App\Models\MainTeacher;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchoolYearTransitionService
{
    /**
     * Preview what the transition will do (dry run)
     */
    public function preview(SchoolYear $sourceYear, array $options = []): array
    {
        $sourceSeries = $this->getSourceSeries($sourceYear);
        $seriesIds = $sourceSeries->pluck('id')->toArray();

        $studentsCount = Student::where('is_active', true)
            ->where(function ($q) use ($sourceYear, $seriesIds) {
                $q->where('school_year_id', $sourceYear->id)
                  ->orWhere(function ($q2) use ($seriesIds) {
                      $q2->whereNull('school_year_id')
                         ->whereIn('class_series_id', $seriesIds);
                  });
            })
            ->count();

        $teacherAssignmentsCount = TeacherAssignment::where(function ($q) use ($sourceYear, $seriesIds) {
                $q->where('school_year_id', $sourceYear->id)
                  ->orWhere(function ($q2) use ($seriesIds) {
                      $q2->whereNull('school_year_id')
                         ->whereHas('classSeriesSubject', function ($q3) use ($seriesIds) {
                             $q3->whereIn('class_series_id', $seriesIds);
                         });
                  });
            })
            ->count();

        $mainTeachersCount = MainTeacher::where(function ($q) use ($sourceYear, $seriesIds) {
                $q->where('school_year_id', $sourceYear->id)
                  ->orWhere(function ($q2) use ($seriesIds) {
                      $q2->whereNull('school_year_id')
                         ->whereIn('class_series_id', $seriesIds);
                  });
            })
            ->count();

        $subjectsCount = ClassSeriesSubject::whereIn('class_series_id', $seriesIds)
            ->where('is_active', true)
            ->count();

        return [
            'source_year' => $sourceYear->name,
            'classes_count' => $sourceSeries->count(),
            'classes' => $sourceSeries->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->schoolClass->name . ' ' . $s->name,
                'students_count' => $s->students()->where('is_active', true)->count(),
            ])->values(),
            'students_count' => $studentsCount,
            'subjects_assignments_count' => $subjectsCount,
            'teacher_assignments_count' => $teacherAssignmentsCount,
            'main_teachers_count' => $mainTeachersCount,
            'options' => [
                'copy_teacher_assignments' => $options['copy_teacher_assignments'] ?? true,
                'copy_main_teachers' => $options['copy_main_teachers'] ?? true,
                'promote_students' => $options['promote_students'] ?? false,
            ],
        ];
    }

    /**
     * Execute the full transition to a new school year
     */
    public function execute(SchoolYear $sourceYear, array $newYearData, array $options = []): array
    {
        $copyTeacherAssignments = $options['copy_teacher_assignments'] ?? true;
        $copyMainTeachers = $options['copy_main_teachers'] ?? true;
        $promoteStudents = $options['promote_students'] ?? false;

        $result = [
            'new_year' => null,
            'trimesters_created' => 0,
            'sequences_created' => 0,
            'classes_created' => 0,
            'subjects_copied' => 0,
            'teacher_assignments_copied' => 0,
            'main_teachers_copied' => 0,
            'students_promoted' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            // 1. Create new school year
            $newYear = $this->createNewYear($newYearData);
            $result['new_year'] = $newYear;

            // 2. Create trimesters and sequences
            $trimesters = $this->createTrimestersAndSequences($newYear, $newYearData);
            $result['trimesters_created'] = count($trimesters);
            $result['sequences_created'] = collect($trimesters)->sum(fn($t) => count($t['sequences']));

            // 3. Fix source year: assign school_year_id to orphan series
            $this->fixSourceYearData($sourceYear);

            // 4. Duplicate class series
            $seriesMapping = $this->duplicateClassSeries($sourceYear, $newYear);
            $result['classes_created'] = count($seriesMapping);

            // 5. Copy class_series_subjects
            $result['subjects_copied'] = $this->copyClassSeriesSubjects($seriesMapping);

            // 6. Copy teacher assignments
            if ($copyTeacherAssignments) {
                $result['teacher_assignments_copied'] = $this->copyTeacherAssignments(
                    $sourceYear, $newYear, $seriesMapping
                );
            }

            // 7. Copy main teachers
            if ($copyMainTeachers) {
                $result['main_teachers_copied'] = $this->copyMainTeachers(
                    $sourceYear, $newYear, $seriesMapping
                );
            }

            // 8. Promote students (move to new year's series)
            if ($promoteStudents) {
                $result['students_promoted'] = $this->promoteStudents(
                    $sourceYear, $newYear, $seriesMapping
                );
            }

            DB::commit();

            Log::info('School year transition completed', $result);

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('School year transition failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create the new school year
     */
    private function createNewYear(array $data): SchoolYear
    {
        return SchoolYear::create([
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_current' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Create 3 trimesters with their sequences for the new year
     */
    private function createTrimestersAndSequences(SchoolYear $newYear, array $yearData): array
    {
        $startDate = \Carbon\Carbon::parse($yearData['start_date']);
        $endDate = \Carbon\Carbon::parse($yearData['end_date']);

        // Divide the year into 3 trimesters roughly
        $totalDays = $startDate->diffInDays($endDate);
        $trimesterDays = intval($totalDays / 3);

        $trimesterNames = [
            1 => '1er Trimestre',
            2 => '2ème Trimestre',
            3 => '3ème Trimestre',
        ];

        $result = [];

        // Sequence configuration per trimester (Premier Cycle format - most common)
        $sequenceConfig = [
            1 => [
                ['name' => '1ère Séquence', 'number' => 1, 'is_composition' => false],
                ['name' => '2ème Séquence', 'number' => 2, 'is_composition' => false],
                ['name' => 'Composition 1', 'number' => 1, 'is_composition' => true],
            ],
            2 => [
                ['name' => '3ème Séquence', 'number' => 3, 'is_composition' => false],
                ['name' => '4ème Séquence', 'number' => 4, 'is_composition' => false],
                ['name' => 'Composition 2', 'number' => 2, 'is_composition' => true],
            ],
            3 => [
                ['name' => '5ème Séquence', 'number' => 5, 'is_composition' => false],
                ['name' => '6ème Séquence', 'number' => 6, 'is_composition' => false],
                ['name' => 'Composition 3', 'number' => 3, 'is_composition' => true],
            ],
        ];

        for ($i = 1; $i <= 3; $i++) {
            $trimStart = $startDate->copy()->addDays(($i - 1) * $trimesterDays);
            $trimEnd = ($i === 3) ? $endDate->copy() : $startDate->copy()->addDays($i * $trimesterDays - 1);

            $trimester = Trimester::create([
                'name' => $trimesterNames[$i],
                'number' => $i,
                'school_year_id' => $newYear->id,
                'start_date' => $trimStart->format('Y-m-d'),
                'end_date' => $trimEnd->format('Y-m-d'),
                'is_active' => true,
                'is_current' => false,
            ]);

            $seqDays = intval($trimStart->diffInDays($trimEnd) / count($sequenceConfig[$i]));
            $sequences = [];

            foreach ($sequenceConfig[$i] as $j => $seqData) {
                $seqStart = $trimStart->copy()->addDays($j * $seqDays);
                $seqEnd = ($j === count($sequenceConfig[$i]) - 1)
                    ? $trimEnd->copy()
                    : $trimStart->copy()->addDays(($j + 1) * $seqDays - 1);

                $sequence = Sequence::create([
                    'name' => $seqData['name'],
                    'number' => $seqData['number'],
                    'trimester_id' => $trimester->id,
                    'school_year_id' => $newYear->id,
                    'start_date' => $seqStart->format('Y-m-d'),
                    'end_date' => $seqEnd->format('Y-m-d'),
                    'is_active' => true,
                    'is_current' => false,
                    'is_completed' => false,
                    'is_composition' => $seqData['is_composition'],
                    'is_locked' => false,
                ]);

                $sequences[] = $sequence->toArray();
            }

            $result[] = [
                'trimester' => $trimester->toArray(),
                'sequences' => $sequences,
            ];
        }

        return $result;
    }

    /**
     * Fix orphan class_series that have no school_year_id
     */
    private function fixSourceYearData(SchoolYear $sourceYear): void
    {
        // Assign source year to series that don't have a year
        ClassSeries::whereNull('school_year_id')
            ->update(['school_year_id' => $sourceYear->id]);

        // Fix students without school_year_id
        Student::whereNull('school_year_id')
            ->whereNotNull('class_series_id')
            ->update(['school_year_id' => $sourceYear->id]);

        // Fix teacher_assignments without school_year_id
        TeacherAssignment::whereNull('school_year_id')
            ->update(['school_year_id' => $sourceYear->id]);

        // Fix main_teachers without school_year_id
        MainTeacher::whereNull('school_year_id')
            ->update(['school_year_id' => $sourceYear->id]);
    }

    /**
     * Duplicate all class_series from source year to new year
     * Returns mapping: [old_series_id => new_series_id]
     */
    private function duplicateClassSeries(SchoolYear $sourceYear, SchoolYear $newYear): array
    {
        $sourceSeries = $this->getSourceSeries($sourceYear);
        $mapping = [];

        foreach ($sourceSeries as $series) {
            // Check if a series with same class_id + name already exists for new year
            $existing = ClassSeries::where('class_id', $series->class_id)
                ->where('name', $series->name)
                ->where('school_year_id', $newYear->id)
                ->first();

            if ($existing) {
                $mapping[$series->id] = $existing->id;
                continue;
            }

            $newSeries = ClassSeries::create([
                'class_id' => $series->class_id,
                'name' => $series->name,
                'code' => $series->code,
                'capacity' => $series->capacity,
                'is_active' => true,
                'main_teacher_id' => null, // Will be set when copying main teachers
                'school_year_id' => $newYear->id,
            ]);

            $mapping[$series->id] = $newSeries->id;
        }

        return $mapping;
    }

    /**
     * Copy class_series_subjects (curriculum) to new series
     */
    private function copyClassSeriesSubjects(array $seriesMapping): int
    {
        $count = 0;

        foreach ($seriesMapping as $oldSeriesId => $newSeriesId) {
            $subjects = ClassSeriesSubject::where('class_series_id', $oldSeriesId)
                ->where('is_active', true)
                ->get();

            foreach ($subjects as $subject) {
                // Check if already exists
                $exists = ClassSeriesSubject::where('class_series_id', $newSeriesId)
                    ->where('subject_id', $subject->subject_id)
                    ->exists();

                if (!$exists) {
                    ClassSeriesSubject::create([
                        'class_series_id' => $newSeriesId,
                        'subject_id' => $subject->subject_id,
                        'coefficient' => $subject->coefficient,
                        'is_active' => true,
                    ]);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Copy teacher assignments to new year
     */
    private function copyTeacherAssignments(SchoolYear $sourceYear, SchoolYear $newYear, array $seriesMapping): int
    {
        $count = 0;

        // Build mapping of old class_series_subject_id => new class_series_subject_id
        $subjectMapping = $this->buildSubjectMapping($seriesMapping);

        $assignments = TeacherAssignment::where('school_year_id', $sourceYear->id)
            ->where('is_active', true)
            ->get();

        foreach ($assignments as $assignment) {
            $newSubjectId = $subjectMapping[$assignment->class_series_subject_id] ?? null;

            if (!$newSubjectId) {
                continue;
            }

            // Check unique constraint
            $exists = TeacherAssignment::where('teacher_id', $assignment->teacher_id)
                ->where('class_series_subject_id', $newSubjectId)
                ->where('school_year_id', $newYear->id)
                ->exists();

            if (!$exists) {
                TeacherAssignment::create([
                    'teacher_id' => $assignment->teacher_id,
                    'class_series_subject_id' => $newSubjectId,
                    'school_year_id' => $newYear->id,
                    'is_active' => true,
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Copy main teacher designations to new year
     */
    private function copyMainTeachers(SchoolYear $sourceYear, SchoolYear $newYear, array $seriesMapping): int
    {
        $count = 0;

        $mainTeachers = MainTeacher::where('school_year_id', $sourceYear->id)
            ->where('is_active', true)
            ->get();

        foreach ($mainTeachers as $mt) {
            $newSeriesId = $seriesMapping[$mt->class_series_id] ?? null;

            if (!$newSeriesId) {
                continue;
            }

            $exists = MainTeacher::where('class_series_id', $newSeriesId)
                ->where('school_year_id', $newYear->id)
                ->exists();

            if (!$exists) {
                MainTeacher::create([
                    'teacher_id' => $mt->teacher_id,
                    'class_series_id' => $newSeriesId,
                    'school_year_id' => $newYear->id,
                    'is_active' => true,
                ]);

                // Update denormalized field on class_series
                ClassSeries::where('id', $newSeriesId)
                    ->update(['main_teacher_id' => $mt->teacher_id]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Promote students: move them to new year's equivalent class series
     * Students stay in the SAME class_id (e.g., 6ème A stays as 6ème A in new year)
     * Actual grade promotion (6ème → 5ème) should be done manually by admin
     */
    private function promoteStudents(SchoolYear $sourceYear, SchoolYear $newYear, array $seriesMapping): int
    {
        $count = 0;

        foreach ($seriesMapping as $oldSeriesId => $newSeriesId) {
            $students = Student::where('class_series_id', $oldSeriesId)
                ->where('school_year_id', $sourceYear->id)
                ->where('is_active', true)
                ->get();

            foreach ($students as $student) {
                $student->update([
                    'class_series_id' => $newSeriesId,
                    'school_year_id' => $newYear->id,
                    'is_new' => false,
                    'student_status' => 'old',
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Build mapping of old ClassSeriesSubject IDs to new ones
     */
    private function buildSubjectMapping(array $seriesMapping): array
    {
        $mapping = [];

        foreach ($seriesMapping as $oldSeriesId => $newSeriesId) {
            $oldSubjects = ClassSeriesSubject::where('class_series_id', $oldSeriesId)->get();
            $newSubjects = ClassSeriesSubject::where('class_series_id', $newSeriesId)->get();

            foreach ($oldSubjects as $oldSubject) {
                $newSubject = $newSubjects->where('subject_id', $oldSubject->subject_id)->first();
                if ($newSubject) {
                    $mapping[$oldSubject->id] = $newSubject->id;
                }
            }
        }

        return $mapping;
    }

    /**
     * Get source series (handles null school_year_id)
     */
    private function getSourceSeries(SchoolYear $sourceYear)
    {
        return ClassSeries::where(function ($q) use ($sourceYear) {
                $q->where('school_year_id', $sourceYear->id)
                  ->orWhereNull('school_year_id');
            })
            ->where('is_active', true)
            ->with('schoolClass')
            ->get();
    }

    /**
     * Activate the new year as current (switch)
     */
    public function activateYear(SchoolYear $newYear): void
    {
        DB::transaction(function () use ($newYear) {
            SchoolYear::where('is_current', true)->update(['is_current' => false]);
            $newYear->update(['is_current' => true, 'is_active' => true]);
        });
    }
}
