<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subname',
        'class_id',
        'sex',
        'fatherName',
        'profession',
        'birthday',
        'birthday_place',
        'email',
        'phone_number',
        'school_year',
        'status',
        'is_new',
        'school_id',
        'inscription',
        'first_tranch',
        'second_tranch',
        'third_tranch',
        'graduation',
        'assurance',
    ];

    protected $casts = [
        'birthday' => 'date',
        'inscription' => 'decimal:2',
        'first_tranch' => 'decimal:2',
        'second_tranch' => 'decimal:2',
        'third_tranch' => 'decimal:2',
        'graduation' => 'decimal:2',
        'assurance' => 'decimal:2',
    ];

    /**
     * Get the class that owns the student
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the notes for the student
     */
    public function notes()
    {
        return $this->hasMany(Note::class, 'student_id');
    }

    /**
     * Get the notes by subject for the student
     */
    public function notesBySubject()
    {
        return $this->hasMany(NoteBySubject::class, 'student_id');
    }

    /**
     * Get the notes by domain for the student
     */
    public function notesByDomain()
    {
        return $this->hasMany(NoteByDomain::class, 'student_id');
    }

    /**
     * Get the stats for the student
     */
    public function stats()
    {
        return $this->hasMany(Stat::class, 'student_id');
    }

    /**
     * Get the payment details for the student
     */
    public function paymentDetails()
    {
        return $this->hasMany(PaymentDetail::class, 'student_id');
    }

    /**
     * Get the full name of the student
     */
    public function getFullNameAttribute()
    {
        return $this->name . ' ' . $this->subname;
    }

    /**
     * Get the total expected amount
     */
    public function getTotalExpectedAttribute()
    {
        return $this->inscription + $this->first_tranch + $this->second_tranch + 
               $this->third_tranch + $this->graduation + $this->assurance;
    }

    /**
     * Get the total paid amount
     */
    public function getTotalPaidAttribute()
    {
        return $this->paymentDetails()->sum('amount');
    }

    /**
     * Get the balance (remaining amount to pay)
     */
    public function getBalanceAttribute()
    {
        return $this->total_expected - $this->total_paid;
    }

    /**
     * Check if student is new
     */
    public function isNew(): bool
    {
        return $this->is_new === 'yes';
    }

    /**
     * Check if student is male
     */
    public function isMale(): bool
    {
        return $this->sex === 'm';
    }

    /**
     * Check if student is female
     */
    public function isFemale(): bool
    {
        return $this->sex === 'f';
    }
}