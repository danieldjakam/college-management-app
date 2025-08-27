<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryCut extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period_id',
        'montant_coupe',
        'motif',
        'date_coupure',
        'created_by',
        'statut',
        'notification_sent'
    ];

    protected $casts = [
        'montant_coupe' => 'decimal:0',
        'date_coupure' => 'date',
        'notification_sent' => 'boolean',
    ];

    // Relations
    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeePayroll::class, 'employee_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'period_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('statut', 'active');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByPeriod($query, $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('statut', $status);
    }

    public function scopeNotificationSent($query)
    {
        return $query->where('notification_sent', true);
    }

    public function scopeNotificationPending($query)
    {
        return $query->where('notification_sent', false);
    }

    // Accessors
    public function getStatutLabelAttribute(): string
    {
        return match($this->statut) {
            'active' => 'Active',
            'annulee' => 'Annulée',
            default => 'Inconnue'
        };
    }

    public function getMontantFormatAttribute(): string
    {
        return number_format($this->montant_coupe, 0, ',', ' ') . ' FCFA';
    }

    // Méthodes métier
    public function isActive(): bool
    {
        return $this->statut === 'active';
    }

    public function isCancelled(): bool
    {
        return $this->statut === 'annulee';
    }

    public function canCancel(): bool
    {
        return $this->statut === 'active' && 
               $this->period->statut !== 'payee';
    }

    public function cancel(): bool
    {
        if (!$this->canCancel()) {
            return false;
        }

        return $this->update(['statut' => 'annulee']);
    }

    public function markNotificationSent(): bool
    {
        return $this->update(['notification_sent' => true]);
    }

    public function getNotificationMessage(): string
    {
        $schoolName = config('app.name', 'École');
        
        return sprintf(
            "🚨 NOTIFICATION DE COUPURE DE SALAIRE\n\n" .
            "Cher(e) %s,\n\n" .
            "Votre salaire du mois de %s a fait l'objet d'une coupure :\n\n" .
            "💰 Montant coupé : %s\n" .
            "📝 Motif : %s\n" .
            "📅 Date : %s\n\n" .
            "Pour plus d'informations, veuillez contacter le service comptabilité.\n\n" .
            "%s",
            $this->employee->nom_complet,
            $this->period->libelle_periode,
            $this->montant_format,
            $this->motif,
            $this->date_coupure->format('d/m/Y'),
            $schoolName
        );
    }
}