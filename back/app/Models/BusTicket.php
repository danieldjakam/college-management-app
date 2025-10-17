<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BusTicket extends Model
{
    protected $fillable = [
        'bus_subscription_id',
        'student_id',
        'month',
        'year',
        'day_number',
        'ticket_date',
        'qr_code_data',
        'ticket_color',
        'is_used',
        'used_at'
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'day_number' => 'integer',
        'ticket_date' => 'date',
        'is_used' => 'boolean',
        'used_at' => 'datetime'
    ];

    /**
     * Relations
     */
    public function subscription()
    {
        return $this->belongsTo(BusSubscription::class, 'bus_subscription_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Obtenir la couleur d'un mois donné
     */
    public static function getMonthColor($month)
    {
        $colors = [
            1 => '#FF4444',  // Janvier - Rouge
            2 => '#4444FF',  // Février - Bleu
            3 => '#44FF44',  // Mars - Vert
            4 => '#FFFF44',  // Avril - Jaune
            5 => '#FF8844',  // Mai - Orange
            6 => '#AA44FF',  // Juin - Violet
            7 => '#FF44AA',  // Juillet - Rose
            8 => '#44FFFF',  // Août - Cyan
            9 => '#AA6644',  // Septembre - Marron
            10 => '#888888', // Octobre - Gris
            11 => '#4444AA', // Novembre - Indigo
            12 => '#44AA88'  // Décembre - Turquoise
        ];

        return $colors[$month] ?? '#888888';
    }

    /**
     * Obtenir le nom du mois en français
     */
    public static function getMonthName($month)
    {
        return Carbon::createFromDate(2025, $month, 1)->locale('fr')->isoFormat('MMMM');
    }

    /**
     * Marquer le ticket comme utilisé
     */
    public function markAsUsed()
    {
        $this->update([
            'is_used' => true,
            'used_at' => now()
        ]);
    }
}
