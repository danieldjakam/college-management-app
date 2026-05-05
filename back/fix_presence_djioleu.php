<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Trouver DJIOLEU FRANCK - chercher dans teachers ET users
$staff = DB::table('teachers')
    ->whereRaw('UPPER(CONCAT(first_name, " ", last_name)) LIKE ?', ['%DJIOLEU%'])
    ->orWhereRaw('UPPER(CONCAT(last_name, " ", first_name)) LIKE ?', ['%DJIOLEU%'])
    ->first();

if ($staff) {
    echo "Trouvé dans teachers: {$staff->first_name} {$staff->last_name} (id:{$staff->id}, user_id:{$staff->user_id})\n";
    $userId = $staff->user_id;
    $staffType = $staff->type_personnel ?? 'P';
    $qrCode = $staff->qr_code ?? 'MANUAL';
} else {
    // Chercher dans users
    $user = DB::table('users')
        ->whereRaw('UPPER(username) LIKE ?', ['%DJIOLEU%'])
        ->orWhereRaw('UPPER(email) LIKE ?', ['%DJIOLEU%'])
        ->orWhereRaw('UPPER(contact) LIKE ?', ['%DJIOLEU%'])
        ->first();

    if (!$user) {
        // Lister tous les users pour trouver manuellement
        echo "DJIOLEU introuvable! Voici tous les users:\n";
        $all = DB::table('users')->select('id', 'username', 'email', 'role')->get();
        foreach ($all as $u) {
            echo "  id:{$u->id} | {$u->username} | {$u->email} | {$u->role}\n";
        }
        exit(1);
    }
    echo "Trouvé dans users: {$user->username} (id:{$user->id}, role:{$user->role})\n";
    $userId = $user->id;
    $staffType = 'P';
    $qrCode = 'MANUAL';
}

$schoolYearId = DB::table('school_years')->where('is_current', true)->value('id');
$now = now();
$count = 0;

// Jours: Avril 2026 (1-30) + Mai 2026 (1-5)
$dates = [];
for ($d = 1; $d <= 30; $d++) {
    $dates[] = '2026-04-' . str_pad($d, 2, '0', STR_PAD_LEFT);
}
for ($d = 1; $d <= 5; $d++) {
    $dates[] = '2026-05-' . str_pad($d, 2, '0', STR_PAD_LEFT);
}

foreach ($dates as $date) {
    $dow = date('N', strtotime($date));
    if ($dow >= 6) continue; // Skip samedi/dimanche

    // Vérifier si déjà enregistré
    $exists = DB::table('staff_attendances')
        ->where('user_id', $userId)
        ->where('attendance_date', $date)
        ->where('event_type', 'entry')
        ->exists();
    if ($exists) continue;

    // Heure arrivée random entre 7h00 et 7h55
    $entryM = rand(0, 55);
    $entryTime = $date . ' 07:' . str_pad($entryM, 2, '0', STR_PAD_LEFT) . ':00';

    // Heure sortie random entre 18h00 et 18h55
    $exitM = rand(0, 55);
    $exitTime = $date . ' 18:' . str_pad($exitM, 2, '0', STR_PAD_LEFT) . ':00';

    $workHours = (18 + $exitM / 60) - (7 + $entryM / 60);

    // Entrée
    DB::table('staff_attendances')->insert([
        'user_id' => $userId,
        'supervisor_id' => $userId,
        'school_year_id' => $schoolYearId,
        'attendance_date' => $date,
        'scanned_at' => $entryTime,
        'scanned_qr_code' => $qrCode,
        'is_present' => 1,
        'event_type' => 'entry',
        'staff_type' => $staffType,
        'work_hours' => 0,
        'late_minutes' => 0,
        'early_departure_minutes' => 0,
        'notes' => 'Présence ajoutée manuellement',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Sortie
    DB::table('staff_attendances')->insert([
        'user_id' => $userId,
        'supervisor_id' => $userId,
        'school_year_id' => $schoolYearId,
        'attendance_date' => $date,
        'scanned_at' => $exitTime,
        'scanned_qr_code' => $qrCode,
        'is_present' => 1,
        'event_type' => 'exit',
        'staff_type' => $staffType,
        'work_hours' => round($workHours, 2),
        'late_minutes' => 0,
        'early_departure_minutes' => 0,
        'notes' => 'Présence ajoutée manuellement',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $count++;
    echo "  ✓ $date (arrivée 07:" . str_pad($entryM, 2, '0', STR_PAD_LEFT) . " - départ 18:" . str_pad($exitM, 2, '0', STR_PAD_LEFT) . ")\n";
}

echo "\nTerminé! $count jours de présence ajoutés (Avril + Mai 2026).\n";
