<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassPaymentAmount extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'payment_tranche_id',
        'amount_new_students',
        'amount_old_students',
        'is_required'
    ];

    protected $casts = [
        'amount_new_students' => 'decimal:2',
        'amount_old_students' => 'decimal:2',
        'is_required' => 'boolean'
    ];

    /**
     * Relation avec la classe
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Relation avec la tranche de paiement
     */
    public function paymentTranche()
    {
        return $this->belongsTo(PaymentTranche::class);
    }

    /**
     * Obtenir le montant selon le type d'étudiant
     */
    public function getAmountForStudent($isNewStudent = true)
    {
        return $isNewStudent ? $this->amount_new_students : $this->amount_old_students;
    }
}