<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BusSubscription extends Model
{
    protected $fillable = [
        'student_id',
        'school_year_id',
        'start_month',
        'start_year',
        'months_count',
        'monthly_price',
        'total_amount',
        'payment_status',
        'purchase_date',
        'purchased_by',
        'notes'
    ];

    protected $casts = [
        'start_month' => 'integer',
        'start_year' => 'integer',
        'months_count' => 'integer',
        'monthly_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'purchase_date' => 'date'
    ];

    /**
     * Relations
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function purchasedBy()
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }

    public function payments()
    {
        return $this->hasMany(BusPayment::class);
    }

    public function tickets()
    {
        return $this->hasMany(BusTicket::class);
    }

    /**
     * Obtenir tous les mois couverts par cet abonnement
     */
    public function getCoveredMonths()
    {
        $months = [];
        $currentMonth = $this->start_month;
        $currentYear = $this->start_year;

        for ($i = 0; $i < $this->months_count; $i++) {
            $months[] = [
                'month' => $currentMonth,
                'year' => $currentYear,
                'label' => Carbon::createFromDate($currentYear, $currentMonth, 1)->locale('fr')->isoFormat('MMMM YYYY')
            ];

            $currentMonth++;
            if ($currentMonth > 12) {
                $currentMonth = 1;
                $currentYear++;
            }
        }

        return $months;
    }

    /**
     * Vérifier si l'abonnement couvre un mois donné
     */
    public function coversMonth($month, $year)
    {
        $coveredMonths = $this->getCoveredMonths();
        foreach ($coveredMonths as $covered) {
            if ($covered['month'] == $month && $covered['year'] == $year) {
                return true;
            }
        }
        return false;
    }
}
