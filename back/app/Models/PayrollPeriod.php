<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'mois',
        'annee',
        'date_debut',
        'date_fin',
        'date_paie',
        'statut',
        'notifications_sent'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_paie' => 'date',
        'notifications_sent' => 'boolean',
    ];

    // Relations
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'period_id');
    }

    public function salaryCuts(): HasMany
    {
        return $this->hasMany(SalaryCut::class, 'period_id');
    }

    public function whatsappNotifications(): HasMany
    {
        return $this->hasMany(PayrollWhatsappNotification::class, 'payroll_period_id');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('statut', $status);
    }

    public function scopeCurrent($query)
    {
        $now = Carbon::now();
        return $query->where('mois', $now->month)
                    ->where('annee', $now->year);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('annee', $year);
    }

    // Accessors
    public function getLibellePeriodeAttribute(): string
    {
        $mois = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        return $mois[$this->mois] . ' ' . $this->annee;
    }

    public function getStatutLabelAttribute(): string
    {
        return match($this->statut) {
            'ouverte' => 'Ouverte',
            'calculee' => 'Calculée',
            'validee' => 'Validée',
            'payee' => 'Payée',
            default => 'Inconnue'
        };
    }

    // Méthodes métier
    public function isOpen(): bool
    {
        return $this->statut === 'ouverte';
    }

    public function isCalculated(): bool
    {
        return in_array($this->statut, ['calculee', 'validee', 'payee']);
    }

    public function isValidated(): bool
    {
        return in_array($this->statut, ['validee', 'payee']);
    }

    public function isPaid(): bool
    {
        return $this->statut === 'payee';
    }

    public function canCalculate(): bool
    {
        return $this->statut === 'ouverte';
    }

    public function canValidate(): bool
    {
        return $this->statut === 'calculee';
    }

    public function canMarkAsPaid(): bool
    {
        return $this->statut === 'validee';
    }

    public function getTotalSalaries(): float
    {
        return $this->payslips()->sum('salaire_net');
    }

    public function getTotalBySalaryMode(string $mode): float
    {
        return $this->payslips()->where('mode_paiement', $mode)->sum('salaire_net');
    }

    public function getEmployeesCount(): int
    {
        return $this->payslips()->count();
    }

    public function getSalaryCutsCount(): int
    {
        return $this->salaryCuts()->where('statut', 'active')->count();
    }

    public function getTotalSalaryCuts(): float
    {
        return $this->salaryCuts()->where('statut', 'active')->sum('montant_coupe');
    }

    // Méthodes de transition d'état
    public function calculate()
    {
        if (!$this->canCalculate()) {
            throw new \Exception('Cette période ne peut pas être calculée.');
        }
        
        $this->update(['statut' => 'calculee']);
    }

    public function validate()
    {
        if (!$this->canValidate()) {
            throw new \Exception('Cette période ne peut pas être validée.');
        }
        
        $this->update(['statut' => 'validee']);
    }

    public function markAsPaid(Carbon $datePaie = null)
    {
        if (!$this->canMarkAsPaid()) {
            throw new \Exception('Cette période ne peut pas être marquée comme payée.');
        }
        
        $this->update([
            'statut' => 'payee',
            'date_paie' => $datePaie ?? Carbon::now()
        ]);
    }

    public function markNotificationsSent()
    {
        $this->update(['notifications_sent' => true]);
    }

    // Factory method
    public static function createForMonth(int $mois, int $annee): self
    {
        $dateDebut = Carbon::createFromDate($annee, $mois, 1);
        $dateFin = $dateDebut->copy()->endOfMonth();
        
        return self::create([
            'mois' => $mois,
            'annee' => $annee,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'statut' => 'ouverte'
        ]);
    }
}