<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniformOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'uniform_order_id',
        'uniform_item_id',
        'uniform_item_variant_id',
        'quantity',
        'unit_price',
        'subtotal'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2'
    ];

    /**
     * Get the order that owns the item
     */
    public function order()
    {
        return $this->belongsTo(UniformOrder::class, 'uniform_order_id');
    }

    /**
     * Get the uniform item
     */
    public function uniformItem()
    {
        return $this->belongsTo(UniformItem::class);
    }

    /**
     * Get the variant
     */
    public function variant()
    {
        return $this->belongsTo(UniformItemVariant::class, 'uniform_item_variant_id');
    }

    /**
     * Calculate subtotal
     */
    public function calculateSubtotal()
    {
        $this->subtotal = $this->quantity * $this->unit_price;
        $this->save();
    }

    /**
     * Boot method to auto-calculate subtotal
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($orderItem) {
            $orderItem->subtotal = $orderItem->quantity * $orderItem->unit_price;
        });
    }
}
