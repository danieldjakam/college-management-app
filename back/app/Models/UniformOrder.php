<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniformOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'student_id',
        'status',
        'total_amount',
        'notes',
        'placed_by',
        'placed_at',
        'ready_by',
        'ready_at',
        'fulfilled_by',
        'fulfilled_at',
        'student_notified',
        'notified_at'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'placed_at' => 'datetime',
        'ready_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'notified_at' => 'datetime',
        'student_notified' => 'boolean'
    ];

    /**
     * Get the student that owns the order
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the user who placed the order
     */
    public function placedBy()
    {
        return $this->belongsTo(User::class, 'placed_by');
    }

    /**
     * Get the user who marked order as ready
     */
    public function readyBy()
    {
        return $this->belongsTo(User::class, 'ready_by');
    }

    /**
     * Get the user who fulfilled the order
     */
    public function fulfilledBy()
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    /**
     * Get the order items
     */
    public function items()
    {
        return $this->hasMany(UniformOrderItem::class);
    }

    /**
     * Get the sale record (if fulfilled)
     */
    public function sale()
    {
        return $this->hasOne(UniformSale::class);
    }

    /**
     * Calculate total amount from items
     */
    public function calculateTotal()
    {
        $this->total_amount = $this->items()->sum('subtotal');
        $this->save();
    }

    /**
     * Mark order as ready for pickup
     */
    public function markAsReady($userId)
    {
        $this->update([
            'status' => 'ready',
            'ready_by' => $userId,
            'ready_at' => now()
        ]);
    }

    /**
     * Mark order as fulfilled
     */
    public function markAsFulfilled($userId)
    {
        $this->update([
            'status' => 'fulfilled',
            'fulfilled_by' => $userId,
            'fulfilled_at' => now()
        ]);

        // Fulfill reservations
        foreach ($this->items as $item) {
            $item->variant->fulfill($item->quantity);
        }
    }

    /**
     * Cancel order
     */
    public function cancel()
    {
        if ($this->status === 'fulfilled') {
            throw new \Exception("Impossible d'annuler une commande déjà retirée");
        }

        // Release reservations
        foreach ($this->items as $item) {
            $item->variant->releaseReservation($item->quantity);
        }

        $this->update(['status' => 'cancelled']);
    }

    /**
     * Notify student
     */
    public function notifyStudent()
    {
        $this->update([
            'student_notified' => true,
            'notified_at' => now()
        ]);
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber()
    {
        $year = date('Y');
        $lastOrder = self::where('order_number', 'like', "ORD-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return "ORD-{$year}-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeFulfilled($query)
    {
        return $query->where('status', 'fulfilled');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}
