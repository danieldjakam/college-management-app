<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationLog extends Model
{
    protected $fillable = [
        'admin_id',
        'title',
        'message',
        'type',
        'priority',
        'send_to_type',
        'send_to_class_id',
        'send_to_student_id',
        'total_recipients',
        'successful_sends',
        'failed_sends',
        'recipients_details',
        'has_attachments',
        'attachments_count'
    ];

    protected $casts = [
        'recipients_details' => 'array',
        'has_attachments' => 'boolean'
    ];

    /**
     * Relation avec l'administrateur qui a envoyé
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Relation avec la classe (si envoi à une classe)
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'send_to_class_id');
    }

    /**
     * Relation avec l'étudiant (si envoi aux parents d'un étudiant spécifique)
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'send_to_student_id');
    }
}
