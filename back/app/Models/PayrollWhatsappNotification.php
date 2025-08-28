<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollWhatsappNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'payroll_period_id',
        'type',
        'message',
        'telephone',
        'statut',
        'error_message',
        'sent_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    // Relations
    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeePayroll::class, 'employee_id');
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    // Scopes
    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByPeriod($query, $periodId)
    {
        return $query->where('payroll_period_id', $periodId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('statut', $status);
    }

    public function scopeSent($query)
    {
        return $query->where('statut', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('statut', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('statut', 'pending');
    }

    // Accessors
    public function getStatutLabelAttribute(): string
    {
        return match($this->statut) {
            'sent' => 'Envoyé',
            'failed' => 'Échec',
            'pending' => 'En attente',
            default => 'Inconnu'
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'salary_cut' => 'Coupure de salaire',
            'salary_available' => 'Salaire disponible',
            'payslip' => 'Bulletin de paie',
            'other' => 'Autre',
            default => 'Inconnu'
        };
    }

    // Méthodes métier
    public function isSent(): bool
    {
        return $this->statut === 'sent';
    }

    public function isFailed(): bool
    {
        return $this->statut === 'failed';
    }

    public function isPending(): bool
    {
        return $this->statut === 'pending';
    }

    public function markAsSent(): bool
    {
        return $this->update([
            'statut' => 'sent',
            'sent_at' => now()
        ]);
    }

    public function markAsFailed(string $errorMessage = null): bool
    {
        return $this->update([
            'statut' => 'failed',
            'error_message' => $errorMessage
        ]);
    }

    public function canRetry(): bool
    {
        return $this->statut === 'failed';
    }

    public function retry(): bool
    {
        if (!$this->canRetry()) {
            return false;
        }

        return $this->update([
            'statut' => 'pending',
            'error_message' => null
        ]);
    }
}