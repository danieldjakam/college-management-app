<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusPayment extends Model
{
    protected $fillable = [
        'bus_subscription_id',
        'amount',
        'payment_date',
        'payment_method',
        'transaction_reference',
        'received_by',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date'
    ];

    /**
     * Relations
     */
    public function subscription()
    {
        return $this->belongsTo(BusSubscription::class, 'bus_subscription_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Obtenir le label du moyen de paiement
     */
    public function getPaymentMethodLabel()
    {
        $labels = [
            'cash' => 'Espèces',
            'bank_transfer' => 'Virement bancaire',
            'mobile_money' => 'Mobile Money',
            'check' => 'Chèque',
            'other' => 'Autre'
        ];

        return $labels[$this->payment_method] ?? 'Inconnu';
    }
}
