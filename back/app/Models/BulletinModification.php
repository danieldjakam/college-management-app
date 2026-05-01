<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulletinModification extends Model
{
    protected $fillable = [
        'student_id',
        'period_type',
        'period_identifier',
        'modifications',
        'reason',
        'modified_by',
    ];

    protected $casts = [
        'modifications' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function modifiedByUser()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
