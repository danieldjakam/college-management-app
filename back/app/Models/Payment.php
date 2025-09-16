<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'school_year_id',
        'total_amount',
        'payment_date',
        'versement_date',
        'validation_date',
        'payment_method',
        'reference_number',
        'notes',
        'created_by_user_id',
        'receipt_number',
        'is_rame_physical',
        'has_scholarship',
        'scholarship_amount',
        'has_reduction',
        'reduction_amount',
        'discount_reason',
        'status',
        'cancellation_reason',
        'status_updated_at',
        'status_updated_by'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'payment_date' => 'date',
        'versement_date' => 'date',
        'validation_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_rame_physical' => 'boolean',
        'has_scholarship' => 'boolean',
        'scholarship_amount' => 'decimal:2',
        'has_reduction' => 'boolean',
        'reduction_amount' => 'decimal:2',
        'status_updated_at' => 'datetime'
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
     * Relation avec l'utilisateur qui a créé le paiement
     */
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour le statut
     */
    public function statusUpdatedBy()
    {
        return $this->belongsTo(User::class, 'status_updated_by');
    }

    /**
     * Relation avec les détails de paiement
     */
    public function paymentDetails()
    {
        return $this->hasMany(PaymentDetail::class);
    }

    /**
     * Générer un numéro de reçu unique
     */
    public static function generateReceiptNumber($schoolYear, $date = null)
    {
        $date = $date ? \Carbon\Carbon::parse($date) : now();
        $yearSuffix = substr($schoolYear->name, -2); // Ex: "25" pour "2024-2025"
        $datePrefix = $date->format('ymd'); // Ex: "250129" pour 29/01/2025
        
        // Générer un numéro unique avec microsecondes pour éviter les doublons
        $microtime = (int) (microtime(true) * 1000000); // Microsecondes depuis epoch
        $uniqueSuffix = substr($microtime, -6); // Prendre les 6 derniers chiffres
        
        // Format : REC + Année(2) + Date(6) + Microtime(6)
        // Ex: REC25250129123456
        return "REC{$yearSuffix}{$datePrefix}{$uniqueSuffix}";
    }

    /**
     * Scope pour filtrer par année scolaire
     */
    public function scopeForYear($query, $yearId)
    {
        return $query->where('school_year_id', $yearId);
    }

    /**
     * Scope pour filtrer par période
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope pour filtrer par étudiant
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Vérifier si le paiement peut être modifié
     */
    public function canBeModified()
    {
        return $this->status === 'pending';
    }

    /**
     * Vérifier si le paiement peut être annulé
     */
    public function canBeCancelled($user = null)
    {
        // Si c'est une comptable supérieur, elle peut supprimer même les paiements annulés
        if ($user && $user->role === 'comptable_superieur') {
            return true;
        }

        // Pour les autres utilisateurs, seulement les paiements pending et validated
        return in_array($this->status, ['pending', 'validated']);
    }

    /**
     * Valider le paiement
     */
    public function validate($userId)
    {
        $this->update([
            'status' => 'validated',
            'status_updated_at' => now(),
            'status_updated_by' => $userId
        ]);
    }

    /**
     * Annuler le paiement
     */
    public function cancel($userId, $reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'status_updated_at' => now(),
            'status_updated_by' => $userId
        ]);
    }
}