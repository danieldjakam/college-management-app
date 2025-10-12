<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'contact',
        'photo',
        'password',
        'role',
        'teacher_id',
        'qualification',
        'is_active',
        'email_verified_at',
        'working_school_year_id',
        'qr_code',
        'staff_identifier',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Relation avec l'année scolaire de travail
     */
    public function workingSchoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'working_school_year_id');
    }

    /**
     * Relation avec le profil enseignant
     */
    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * Générer l'identifiant personnel selon le rôle
     */
    public function generateStaffIdentifier()
    {
        // Vérifier si la colonne staff_identifier existe dans les attributs
        if (isset($this->attributes['staff_identifier']) && $this->attributes['staff_identifier']) {
            return $this->attributes['staff_identifier'];
        }

        $prefix = $this->role === 'teacher' ? 'TCH_' : 'STAF_';
        $identifier = $prefix . $this->id;

        // Essayer de sauvegarder seulement si la colonne existe
        try {
            $this->update(['staff_identifier' => $identifier]);
        } catch (\Exception $e) {
            // Si la colonne n'existe pas, on retourne juste l'identifiant généré
            \Log::info("staff_identifier column might not exist for user {$this->id}");
        }
        
        return $identifier;
    }

    /**
     * Accessor pour obtenir l'identifiant personnel
     */
    public function getStaffIdentifierAttribute($value)
    {
        if (!$value) {
            return $this->generateStaffIdentifier();
        }
        return $value;
    }
}
