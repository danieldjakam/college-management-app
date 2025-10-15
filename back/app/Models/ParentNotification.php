<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'student_id',
        'admin_id',
        'title',
        'message',
        'type',
        'priority',
        'is_read',
        'read_at',
        'whatsapp_sent',
        'whatsapp_sent_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'whatsapp_sent' => 'boolean',
        'whatsapp_sent_at' => 'datetime'
    ];

    // Types de notifications
    const TYPE_ABSENCE = 'absence';
    const TYPE_GRADE = 'grade';
    const TYPE_PAYMENT = 'payment';
    const TYPE_DISCIPLINE = 'discipline';
    const TYPE_MESSAGE = 'message';
    const TYPE_EVENT = 'event';
    const TYPE_GENERAL = 'general';

    // Priorités
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Relation avec le parent
     */
    public function parent()
    {
        return $this->belongsTo(ParentGuardian::class, 'parent_id');
    }

    /**
     * Relation avec l'étudiant concerné
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Relation avec l'admin qui a envoyé la notification
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Relation avec les pièces jointes
     */
    public function attachments()
    {
        return $this->hasMany(ParentNotificationAttachment::class);
    }

    /**
     * Marquer comme lu
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    /**
     * Scope pour les notifications non lues
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope par priorité
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Obtenir la couleur selon la priorité
     */
    public function getPriorityColorAttribute()
    {
        return [
            self::PRIORITY_LOW => 'success',
            self::PRIORITY_NORMAL => 'info',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_URGENT => 'danger'
        ][$this->priority] ?? 'secondary';
    }

    /**
     * Obtenir l'icône selon le type
     */
    public function getTypeIconAttribute()
    {
        return [
            self::TYPE_ABSENCE => 'calendar-x',
            self::TYPE_GRADE => 'clipboard-data',
            self::TYPE_PAYMENT => 'credit-card',
            self::TYPE_DISCIPLINE => 'exclamation-triangle',
            self::TYPE_MESSAGE => 'envelope',
            self::TYPE_EVENT => 'calendar-event',
            self::TYPE_GENERAL => 'info-circle'
        ][$this->type] ?? 'bell';
    }
}