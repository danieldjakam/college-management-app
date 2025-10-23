<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniformItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'base_price',
        'category',
        'image_url',
        'is_active',
        'reorder_level'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
        'reorder_level' => 'integer'
    ];

    /**
     * Get the variants for the uniform item
     */
    public function variants()
    {
        return $this->hasMany(UniformItemVariant::class);
    }

    /**
     * Get the order items for this uniform item
     */
    public function orderItems()
    {
        return $this->hasMany(UniformOrderItem::class);
    }

    /**
     * Get total stock across all variants
     */
    public function getTotalStockAttribute()
    {
        return $this->variants()->sum('stock_quantity');
    }

    /**
     * Get total available stock across all variants
     */
    public function getTotalAvailableStockAttribute()
    {
        return $this->variants()->sum('available_quantity');
    }

    /**
     * Check if item needs restocking
     */
    public function needsRestock()
    {
        return $this->getTotalAvailableStockAttribute() <= $this->reorder_level;
    }

    /**
     * Scope to get active items only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get items by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
