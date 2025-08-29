<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeePayroll extends Model
{
    use HasFactory;

    protected $table = 'employees_payroll';

    protected $fillable = [
        'user_id',
        'matricule',
        'nom',
        'prenom',
        'poste',
        'department',
        'salaire_base',
        'primes_fixes',
        'deductions_fixes',
        'mode_paiement',
        'telephone_whatsapp',
        'statut'
    ];

    protected $casts = [
        'salaire_base' => 'decimal:0',
        'primes_fixes' => 'decimal:0',
        'deductions_fixes' => 'decimal:0',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'employee_id');
    }

    public function salaryCuts(): HasMany
    {
        return $this->hasMany(SalaryCut::class, 'employee_id');
    }

    public function whatsappNotifications(): HasMany
    {
        return $this->hasMany(PayrollWhatsappNotification::class, 'employee_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeByPaymentMode($query, $mode)
    {
        return $query->where('mode_paiement', $mode);
    }

    // Accessors
    public function getNomCompletAttribute(): string
    {
        return $this->nom . ' ' . $this->prenom;
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

    // Méthodes métier
    public function getSalaireBrut(int $primes_mensuelles = 0): float
    {
        return $this->salaire_base + $this->primes_fixes + $primes_mensuelles;
    }

    public function getTotalCoupures($period_id): float
    {
        return $this->salaryCuts()
            ->where('period_id', $period_id)
            ->where('statut', 'active')
            ->sum('montant_coupe');
    }

    public function getSalaireNet(int $primes_mensuelles = 0, int $deductions_mensuelles = 0, $period_id = null): float
    {
        $brut = $this->getSalaireBrut($primes_mensuelles);
        $total_deductions = $this->deductions_fixes + $deductions_mensuelles;
        
        if ($period_id) {
            $total_deductions += $this->getTotalCoupures($period_id);
        }
        
        return max(0, $brut - $total_deductions);
    }

    public function hasActiveCuts($period_id): bool
    {
        return $this->salaryCuts()
            ->where('period_id', $period_id)
            ->where('statut', 'active')
            ->exists();
    }
}