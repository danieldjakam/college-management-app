<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniformItemVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'uniform_item_id',
        'size',
        'price_adjustment',
        'sku',
        'stock_quantity',
        'reserved_quantity'
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'stock_quantity' => 'integer',
        'reserved_quantity' => 'integer'
    ];

    protected $appends = ['final_price', 'available_quantity'];

    /**
     * Get the uniform item that owns the variant
     */
    public function uniformItem()
    {
        return $this->belongsTo(UniformItem::class);
    }

    /**
     * Get the order items for this variant
     */
    public function orderItems()
    {
        return $this->hasMany(UniformOrderItem::class);
    }

    /**
     * Get the final price (base price + adjustment)
     */
    public function getFinalPriceAttribute()
    {
        return $this->uniformItem->base_price + $this->price_adjustment;
    }

    /**
     * Get available quantity (stock - reserved)
     */
    public function getAvailableQuantityAttribute()
    {
        return $this->stock_quantity - $this->reserved_quantity;
    }

    /**
     * Reserve quantity for an order
     */
    public function reserve($quantity)
    {
        if ($this->getAvailableQuantityAttribute() < $quantity) {
            throw new \Exception("Stock insuffisant pour réserver {$quantity} article(s)");
        }

        $this->increment('reserved_quantity', $quantity);
    }

    /**
     * Release reserved quantity (when order cancelled)
     */
    public function releaseReservation($quantity)
    {
        $this->decrement('reserved_quantity', $quantity);
    }

    /**
     * Fulfill reservation (when order completed)
     */
    public function fulfill($quantity)
    {
        if ($this->reserved_quantity < $quantity) {
            throw new \Exception("Quantité réservée insuffisante");
        }

        $this->decrement('reserved_quantity', $quantity);
        $this->decrement('stock_quantity', $quantity);
    }

    /**
     * Add stock
     */
    public function addStock($quantity)
    {
        $this->increment('stock_quantity', $quantity);
    }

    /**
     * Check if variant has available stock
     */
    public function hasAvailableStock($quantity = 1)
    {
        return $this->getAvailableQuantityAttribute() >= $quantity;
    }

    /**
     * Scope to get variants with stock
     */
    public function scopeInStock($query)
    {
        return $query->whereRaw('stock_quantity - reserved_quantity > 0');
    }
}
