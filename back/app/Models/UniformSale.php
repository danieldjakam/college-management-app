<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniformSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'uniform_order_id',
        'receipt_number',
        'student_id',
        'total_amount',
        'payment_method',
        'payment_reference',
        'processed_by',
        'sale_date',
        'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'sale_date' => 'datetime'
    ];

    /**
     * Get the order associated with this sale
     */
    public function order()
    {
        return $this->belongsTo(UniformOrder::class, 'uniform_order_id');
    }

    /**
     * Get the student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the user who processed the sale
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Generate unique receipt number
     */
    public static function generateReceiptNumber()
    {
        $year = date('Y');
        $lastSale = self::where('receipt_number', 'like', "REC-UNIF-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastSale) {
            $lastNumber = (int) substr($lastSale->receipt_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return "REC-UNIF-{$year}-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scope for date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('sale_date', [$startDate, $endDate]);
    }

    /**
     * Scope for today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('sale_date', today());
    }

    /**
     * Scope by payment method
     */
    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }
}
