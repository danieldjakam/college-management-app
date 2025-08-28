<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period_id',
        'salaire_base',
        'primes_mensuelles',
        'deductions_mensuelles',
        'montant_coupures',
        'salaire_brut',
        'salaire_net',
        'mode_paiement',
        'statut',
        'retire',
        'date_retrait'
    ];

    protected $casts = [
        'salaire_base' => 'decimal:0',
        'primes_mensuelles' => 'decimal:0',
        'deductions_mensuelles' => 'decimal:0',
        'montant_coupures' => 'decimal:0',
        'salaire_brut' => 'decimal:0',
        'salaire_net' => 'decimal:0',
        'retire' => 'boolean',
        'date_retrait' => 'date',
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

    // Scopes
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

    public function scopeByPaymentMode($query, $mode)
    {
        return $query->where('mode_paiement', $mode);
    }

    public function scopeRetired($query)
    {
        return $query->where('retire', true);
    }

    public function scopeNotRetired($query)
    {
        return $query->where('retire', false);
    }

    public function scopeValidated($query)
    {
        return $query->whereIn('statut', ['valide', 'paye']);
    }

    // Accessors
    public function getStatutLabelAttribute(): string
    {
        return match($this->statut) {
            'brouillon' => 'Brouillon',
            'valide' => 'Validé',
            'paye' => 'Payé',
            default => 'Inconnue'
        };
    }

    public function getModePaiementLabelAttribute(): string
    {
        return match($this->mode_paiement) {
            'especes' => 'Espèces',
            'cheque' => 'Chèque',
            'virement' => 'Virement',
            default => 'Non défini'
        };
    }

    public function getSalaireNetFormatAttribute(): string
    {
        return number_format($this->salaire_net, 0, ',', ' ') . ' FCFA';
    }

    public function getSalaireBrutFormatAttribute(): string
    {
        return number_format($this->salaire_brut, 0, ',', ' ') . ' FCFA';
    }

    // Méthodes métier
    public function isDraft(): bool
    {
        return $this->statut === 'brouillon';
    }

    public function isValidated(): bool
    {
        return in_array($this->statut, ['valide', 'paye']);
    }

    public function isPaid(): bool
    {
        return $this->statut === 'paye';
    }

    public function isRetired(): bool
    {
        return $this->retire;
    }

    public function canValidate(): bool
    {
        return $this->statut === 'brouillon';
    }

    public function canMarkAsPaid(): bool
    {
        return $this->statut === 'valide';
    }

    public function canMarkAsRetired(): bool
    {
        return $this->statut === 'paye' && !$this->retire;
    }

    public function validate(): bool
    {
        if (!$this->canValidate()) {
            return false;
        }

        return $this->update(['statut' => 'valide']);
    }

    public function markAsPaid(): bool
    {
        if (!$this->canMarkAsPaid()) {
            return false;
        }

        return $this->update(['statut' => 'paye']);
    }

    public function markAsRetired(): bool
    {
        if (!$this->canMarkAsRetired()) {
            return false;
        }

        return $this->update([
            'retire' => true,
            'date_retrait' => now()
        ]);
    }

    public function calculateSalary(): void
    {
        // Calcul du brut
        $this->salaire_brut = $this->salaire_base + $this->primes_mensuelles;
        
        // Calcul des déductions totales
        $total_deductions = $this->deductions_mensuelles + $this->montant_coupures;
        
        // Calcul du net
        $this->salaire_net = max(0, $this->salaire_brut - $total_deductions);
        
        $this->save();
    }

    public function getSalaryAvailableMessage(): string
    {
        $schoolName = config('app.name', 'École');
        
        return sprintf(
            "💰 SALAIRE DISPONIBLE\n\n" .
            "Cher(e) %s,\n\n" .
            "Votre salaire du mois de %s est maintenant disponible à la comptabilité.\n\n" .
            "💵 Salaire net : %s\n" .
            "🏦 Mode de paiement : %s\n" .
            "📍 Lieu de retrait : Comptabilité\n\n" .
            "Veuillez vous présenter aux heures d'ouverture avec votre pièce d'identité.\n\n" .
            "%s\n" .
            "📞 Comptabilité : %s",
            $this->employee->nom_complet,
            $this->period->libelle_periode,
            $this->salaire_net_format,
            $this->mode_paiement_label,
            $schoolName,
            config('school.phone_comptabilite', '')
        );
    }

    // Méthode factory pour créer un bulletin
    public static function createFromEmployee(EmployeePayroll $employee, PayrollPeriod $period): self
    {
        $coupures = $employee->getTotalCoupures($period->id);
        
        $payslip = self::create([
            'employee_id' => $employee->id,
            'period_id' => $period->id,
            'salaire_base' => $employee->salaire_base,
            'primes_mensuelles' => 0,
            'deductions_mensuelles' => $employee->deductions_fixes,
            'montant_coupures' => $coupures,
            'mode_paiement' => $employee->mode_paiement,
            'statut' => 'brouillon'
        ]);

        $payslip->calculateSalary();
        
        return $payslip;
    }
}