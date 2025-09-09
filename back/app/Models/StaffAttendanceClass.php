<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffAttendanceClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_attendance_id',
        'school_class_id'
    ];

    /**
     * Relation avec StaffAttendance
     */
    public function staffAttendance()
    {
        return $this->belongsTo(StaffAttendance::class);
    }

    /**
     * Relation avec SchoolClass
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }
}
