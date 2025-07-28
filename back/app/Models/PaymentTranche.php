<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTranche extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer'
    ];

    /**
     * Relation avec les montants de classes
     */
    public function classPaymentAmounts()
    {
        return $this->hasMany(ClassPaymentAmount::class);
    }

    /**
     * Scope pour les tranches actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour ordonner les tranches
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}