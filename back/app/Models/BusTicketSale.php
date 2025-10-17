<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusTicketSale extends Model
{
    protected $fillable = [
        'batch_id',
        'ticket_number',
        'student_id',
        'ticket_type',
        'price',
        'payment_method',
        'sold_by',
        'sold_at',
        'qr_code_data',
        'is_used',
        'used_at',
        'notes'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_used' => 'boolean',
        'sold_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Relation: Lot de tickets
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(BusTicketBatch::class, 'batch_id');
    }

    /**
     * Relation: Élève
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Relation: Vendu par (User)
     */
    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    /**
     * Marquer le ticket comme utilisé
     */
    public function markAsUsed(): void
    {
        $this->update([
            'is_used' => true,
            'used_at' => now()
        ]);
    }

    /**
     * Scope: Tickets non utilisés
     */
    public function scopeUnused($query)
    {
        return $query->where('is_used', false);
    }

    /**
     * Scope: Tickets utilisés
     */
    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }

    /**
     * Scope: Par date de vente
     */
    public function scopeBySaleDate($query, $date)
    {
        return $query->whereDate('sold_at', $date);
    }

    /**
     * Scope: Par type de ticket
     */
    public function scopeByType($query, $type)
    {
        return $query->where('ticket_type', $type);
    }
}
