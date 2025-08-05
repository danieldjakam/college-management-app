<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StudentScholarship extends Model
{
    protected $fillable = [
        'student_id',
        'class_scholarship_id',
        'payment_tranche_id',
        'is_used',
        'used_at',
        'amount_used',
        'notes'
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
        'amount_used' => 'decimal:2'
    ];

    /**
     * Relation avec l'étudiant
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relation avec la bourse de classe
     */
    public function classScholarship()
    {
        return $this->belongsTo(ClassScholarship::class);
    }

    /**
     * Relation avec la tranche de paiement
     */
    public function paymentTranche()
    {
        return $this->belongsTo(PaymentTranche::class);
    }

    /**
     * Scope pour les bourses non utilisées
     */
    public function scopeUnused($query)
    {
        return $query->where('is_used', false);
    }

    /**
     * Scope pour les bourses utilisées
     */
    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }

    /**
     * Marquer la bourse comme utilisée
     */
    public function markAsUsed(float $amountUsed = null)
    {
        $this->update([
            'is_used' => true,
            'used_at' => Carbon::now(),
            'amount_used' => $amountUsed ?? $this->classScholarship->amount
        ]);
    }

    /**
     * Vérifier si la bourse est encore valide (non utilisée et dans les délais)
     */
    public function isValid(): bool
    {
        if ($this->is_used) {
            return false;
        }

        // Vérifier si la tranche a une deadline et si on est dans les délais
        if ($this->paymentTranche && $this->paymentTranche->deadline) {
            return Carbon::now()->lte($this->paymentTranche->deadline);
        }

        return true;
    }

    /**
     * Obtenir le montant disponible de la bourse
     */
    public function getAvailableAmount(): float
    {
        if ($this->is_used) {
            return 0;
        }

        return $this->classScholarship ? $this->classScholarship->amount : 0;
    }
}