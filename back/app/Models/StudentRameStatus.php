<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentRameStatus extends Model
{
    use HasFactory;

    protected $table = 'student_rame_status';

    protected $fillable = [
        'student_id',
        'school_year_id',
        'has_brought_rame',
        'marked_date',
        'marked_by_user_id',
        'notes'
    ];

    protected $casts = [
        'has_brought_rame' => 'boolean',
        'marked_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relation avec l'étudiant
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relation avec l'année scolaire
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Relation avec l'utilisateur qui a marqué le statut
     */
    public function markedByUser()
    {
        return $this->belongsTo(User::class, 'marked_by_user_id');
    }

    /**
     * Obtenir ou créer le statut RAME pour un étudiant
     */
    public static function getOrCreateForStudent($studentId, $schoolYearId)
    {
        return self::firstOrCreate(
            [
                'student_id' => $studentId,
                'school_year_id' => $schoolYearId
            ],
            [
                'has_brought_rame' => false,
                'marked_date' => null,
                'marked_by_user_id' => null,
                'notes' => null
            ]
        );
    }

    /**
     * Marquer comme ayant apporté la RAME
     */
    public function markAsBrought($userId = null, $notes = null)
    {
        $this->update([
            'has_brought_rame' => true,
            'marked_date' => now()->toDateString(),
            'marked_by_user_id' => $userId,
            'notes' => $notes
        ]);
    }

    /**
     * Marquer comme n'ayant pas apporté la RAME
     */
    public function markAsNotBrought($userId = null, $notes = null)
    {
        $this->update([
            'has_brought_rame' => false,
            'marked_date' => now()->toDateString(),
            'marked_by_user_id' => $userId,
            'notes' => $notes
        ]);
    }
}