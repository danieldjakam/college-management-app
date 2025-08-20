<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentaryFee extends Model
{
    use HasFactory;

    protected $table = 'documentary_fees';

    protected $fillable = [
        'student_id',
        'school_year_id',
        'fee_type',
        'description',
        'fee_amount',
        'penalty_amount',
        'total_amount',
        'payment_date',
        'versement_date',
        'validation_date',
        'payment_method',
        'reference_number',
        'receipt_number',
        'notes',
        'status',
        'created_by_user_id',
        'validated_by_user_id'
    ];

    protected $casts = [
        'fee_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_date' => 'date',
        'versement_date' => 'date',
        'validation_date' => 'datetime',
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
     * Relation avec l'utilisateur qui a créé le frais
     */
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Relation avec l'utilisateur qui a validé le frais
     */
    public function validatedByUser()
    {
        return $this->belongsTo(User::class, 'validated_by_user_id');
    }

    /**
     * Générer un numéro de reçu unique pour les frais de dossier
     */
    public static function generateReceiptNumber($schoolYear, $isPenalty = false, $date = null)
    {
        $date = $date ? \Carbon\Carbon::parse($date) : now();
        $yearSuffix = substr($schoolYear->name, -2); // Ex: "25" pour "2024-2025"
        $datePrefix = $date->format('ymd'); // Ex: "250129" pour 29/01/2025

        // Préfixe selon si c'est une pénalité ou non
        $typePrefix = $isPenalty ? 'PEN' : 'FD';

        // Générer un numéro unique avec microsecondes pour éviter les doublons
        $microtime = (int) (microtime(true) * 1000000); // Microsecondes depuis epoch
        $uniqueSuffix = substr($microtime, -6); // Prendre les 6 derniers chiffres

        // Format : TypePrefix + Année(2) + Date(6) + Microtime(6)
        // Ex: FD25250129123456, PEN25250129123456
        return "{$typePrefix}{$yearSuffix}{$datePrefix}{$uniqueSuffix}";
    }

    /**
     * Scope pour filtrer par année scolaire
     */
    public function scopeForYear($query, $yearId)
    {
        return $query->where('school_year_id', $yearId);
    }

    /**
     * Scope pour filtrer par pénalité
     */
    public function scopeWithPenalty($query)
    {
        return $query->where('penalty_amount', '>', 0);
    }

    /**
     * Scope pour filtrer sans pénalité
     */
    public function scopeWithoutPenalty($query)
    {
        return $query->where('penalty_amount', 0);
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
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
     * Scope pour les frais validés
     */
    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    /**
     * Scope pour les frais en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Obtenir le libellé du type de frais
     */
    public function getFeeTypeLabel()
    {
        if ($this->penalty_amount > 0) {
            return 'Frais de dossier + Pénalité';
        }
        return 'Frais de dossier';
    }

    /**
     * Vérifier si le frais contient une pénalité
     */
    public function hasPenalty()
    {
        return $this->penalty_amount > 0;
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getStatusLabel()
    {
        return match($this->status) {
            'pending' => 'En attente',
            'validated' => 'Validé',
            'cancelled' => 'Annulé',
            default => $this->status
        };
    }
}
