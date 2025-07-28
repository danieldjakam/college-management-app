<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'payments_details';

    protected $fillable = [
        'student_id',
        'operator_id',
        'amount',
        'recu_name',
        'tag',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the student that owns the payment
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get the operator (user) who made the payment
     */
    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}