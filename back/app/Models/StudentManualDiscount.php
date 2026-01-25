<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentManualDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'school_year_id',
        'created_by',
        'amount',
        'reason',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
