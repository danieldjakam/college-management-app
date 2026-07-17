<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentArrear extends Model
{
    protected $fillable = [
        'student_id',
        'source_school_year_id',
        'target_school_year_id',
        'total_required',
        'total_paid',
        'arrear_amount',
        'source_class_name',
        'status',
        'amount_paid_on_arrear',
        'notes',
    ];

    protected $casts = [
        'total_required' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'arrear_amount' => 'decimal:2',
        'amount_paid_on_arrear' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function sourceYear()
    {
        return $this->belongsTo(SchoolYear::class, 'source_school_year_id');
    }

    public function targetYear()
    {
        return $this->belongsTo(SchoolYear::class, 'target_school_year_id');
    }

    public function getRemainingArrearAttribute()
    {
        return max(0, $this->arrear_amount - $this->amount_paid_on_arrear);
    }
}
