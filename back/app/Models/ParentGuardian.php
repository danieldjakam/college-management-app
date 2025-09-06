<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class ParentGuardian extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'parent_guardians';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'secondary_phone',
        'password',
        'pin_code',
        'address',
        'city',
        'profession',
        'notification_preferences',
        'language_preference',
        'is_active',
        'last_login_at'
    ];

    protected $hidden = [
        'password',
        'pin_code',
        'remember_token',
    ];

    protected $casts = [
        'notification_preferences' => 'array',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Relation avec les enfants (students)
     */
    public function children()
    {
        return $this->belongsToMany(Student::class, 'parent_student_relationships', 'parent_id', 'student_id')
                    ->withPivot(['relationship_type', 'is_primary_contact', 'can_pick_up', 'emergency_contact'])
                    ->withTimestamps();
    }

    /**
     * Notifications du parent
     */
    public function notifications()
    {
        return $this->hasMany(ParentNotification::class, 'parent_id');
    }

    /**
     * Messages reçus
     */
    public function messages()
    {
        return $this->hasMany(ParentMessage::class, 'parent_id');
    }

    /**
     * Obtenir les notifications non lues
     */
    public function unreadNotifications()
    {
        return $this->notifications()->where('is_read', false);
    }

    /**
     * Obtenir les enfants actifs
     */
    public function activeChildren()
    {
        return $this->children()->where('students.status', 'active');
    }

    /**
     * Nom complet
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}