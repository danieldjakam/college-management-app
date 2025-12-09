<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulletinGeneration extends Model
{
    use HasFactory;

    protected $table = 'bulletin_generations';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'student_id',
        'template_id',
        'period_type',
        'period_identifier',
        'file_path',
        'generated_at',
        'completion_percentage',
        'is_complete',
        'notes'
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'completion_percentage' => 'decimal:2',
        'is_complete' => 'boolean'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function template()
    {
        return $this->belongsTo(BulletinTemplate::class, 'template_id');
    }

    public function scopeByPeriod($query, $type, $identifier)
    {
        return $query->where('period_type', $type)
                     ->where('period_identifier', $identifier);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }
}
