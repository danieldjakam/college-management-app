<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusTicketBatch extends Model
{
    protected $fillable = [
        'batch_date',
        'ticket_type',
        'quantity_generated',
        'quantity_sold',
        'quantity_remaining',
        'price_per_ticket',
        'batch_prefix',
        'start_number',
        'end_number',
        'generated_by',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'batch_date' => 'date',
        'price_per_ticket' => 'decimal:2',
        'is_active' => 'boolean',
        'quantity_generated' => 'integer',
        'quantity_sold' => 'integer',
        'quantity_remaining' => 'integer',
    ];

    /**
     * Relation: Généré par (User)
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Relation: Ventes de tickets
     */
    public function sales(): HasMany
    {
        return $this->hasMany(BusTicketSale::class, 'batch_id');
    }

    /**
     * Mettre à jour les quantités après une vente
     */
    public function decrementStock(): void
    {
        $this->decrement('quantity_remaining');
        $this->increment('quantity_sold');
    }

    /**
     * Vérifier si le stock est disponible
     */
    public function hasStock(): bool
    {
        return $this->quantity_remaining > 0;
    }

    /**
     * Obtenir le prochain numéro de ticket disponible
     */
    public function getNextTicketNumber(): string
    {
        $lastSale = $this->sales()->orderBy('id', 'desc')->first();

        if (!$lastSale) {
            // Premier ticket du lot
            return $this->batch_prefix . '-' . str_pad($this->start_number, 3, '0', STR_PAD_LEFT);
        }

        // Extraire le numéro du dernier ticket vendu et incrémenter
        $lastNumber = (int) substr($lastSale->ticket_number, strrpos($lastSale->ticket_number, '-') + 1);
        $nextNumber = $lastNumber + 1;

        return $this->batch_prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Scope: Lots actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Par date
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('batch_date', $date);
    }

    /**
     * Scope: Par type de ticket
     */
    public function scopeByType($query, $type)
    {
        return $query->where('ticket_type', $type);
    }
}
