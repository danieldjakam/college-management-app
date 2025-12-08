<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCard extends Model
{
    protected $fillable = [
        'student_id',
        'qr_code_data',
        'academic_year',
        'card_number',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    /**
     * Relation avec l'élève
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
