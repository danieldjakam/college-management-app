<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Teacher extends Authenticatable implements JWTSubject
{
    use HasFactory;

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'subname',
        'class_id',
        'matricule',
        'password',
        'sex',
        'phone_number',
        'birthday',
        'school_id',
        'school_year',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'birthday' => 'date',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => 'teacher',
            'school_id' => $this->school_id,
            'class_id' => $this->class_id,
        ];
    }

    /**
     * Get the class assigned to this teacher
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the full name of the teacher
     */
    public function getFullNameAttribute()
    {
        return $this->name . ' ' . $this->subname;
    }

    /**
     * Check if teacher is male
     */
    public function isMale(): bool
    {
        return $this->sex === 'm';
    }

    /**
     * Check if teacher is female
     */
    public function isFemale(): bool
    {
        return $this->sex === 'f';
    }

    /**
     * Generate a new random password for the teacher
     */
    public function generateNewPassword(): string
    {
        $newPassword = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $this->password = $newPassword;
        $this->save();
        
        return $newPassword;
    }
}