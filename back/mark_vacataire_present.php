<?php
/**
 * Script pour marquer automatiquement les personnels vacataires comme présents
 * avec des heures d'arrivée aléatoires entre 6h10 et 8h14
 * Usage: php mark_vacataire_present.php [date] [school_year_id]
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\StaffAttendance;
use App\Models\SchoolYear;
use App\Services\WhatsAppService;
use Carbon\Carbon;

echo "===========================================\n";
echo "MARQUAGE AUTOMATIQUE DES VACATAIRES\n";
echo "===========================================\n\n";

// Récupérer les paramètres
$targetDate = $argv[1] ?? Carbon::now()->format('Y-m-d');
$schoolYearId = $argv[2] ?? null;

echo "Date cible: $targetDate\n";

// Vérifier si la date est valide
try {
    $date = Carbon::parse($targetDate);
} catch (Exception $e) {
    echo "❌ Date invalide: $targetDate\n";
    echo "Format attendu: YYYY-MM-DD (ex: 2024-01-15)\n";
    exit(1);
}

// Obtenir l'année scolaire
if (!$schoolYearId) {
    $currentSchoolYear = SchoolYear::where('is_current', 1)->first();
    if (!$currentSchoolYear) {
        echo "❌ Aucune année scolaire active trouvée\n";
        exit(1);
    }
    $schoolYearId = $currentSchoolYear->id;
    echo "Année scolaire: {$currentSchoolYear->name}\n";
} else {
    $schoolYear = SchoolYear::find($schoolYearId);
    if (!$schoolYear) {
        echo "❌ Année scolaire ID $schoolYearId introuvable\n";
        exit(1);
    }
    echo "Année scolaire: {$schoolYear->name}\n";
}

// Rechercher tous les utilisateurs avec des rôles qui pourraient être vacataires
echo "\n🔍 Recherche des personnels vacataires...\n";

// D'abord, chercher les présences existantes de type 'vacataire' pour avoir une idée
$existingVacataires = StaffAttendance::where('staff_type', 'vacataire')
    ->distinct()
    ->pluck('user_id')
    ->toArray();

echo "Personnel déjà marqué comme vacataire: " . count($existingVacataires) . " personnes\n";

// Chercher les utilisateurs qui pourraient être vacataires
// (enseignants, personnel divers, etc.)
$potentialVacataires = User::whereIn('role', ['teacher', 'user', 'surveillant_general'])
    ->whereIn('id', $existingVacataires) // Ne traiter que ceux déjà identifiés comme vacataires
    ->where('is_active', 1)
    ->get();

if ($potentialVacataires->isEmpty()) {
    echo "⚠️ Aucun personnel vacataire trouvé.\n";
    echo "Tentative de recherche plus large...\n";
    
    // Recherche plus large si aucun vacataire spécifique trouvé
    $potentialVacataires = User::where('role', 'teacher')
        ->where('is_active', 1)
        ->limit(10) // Limiter pour éviter de marquer tout le monde
        ->get();
}

echo "Personnel vacataire trouvé: " . $potentialVacataires->count() . " personnes\n\n";

if ($potentialVacataires->isEmpty()) {
    echo "❌ Aucun personnel à traiter\n";
    exit(1);
}

// Vérifier s'il y a déjà des présences pour cette date
$existingAttendances = StaffAttendance::whereDate('attendance_date', $date)
    ->where('staff_type', 'vacataire')
    ->whereIn('user_id', $potentialVacataires->pluck('id'))
    ->get();

if ($existingAttendances->count() > 0) {
    echo "⚠️ Il existe déjà " . $existingAttendances->count() . " présences vacataires pour le $targetDate\n";
    echo "Voulez-vous continuer et ajouter/mettre à jour? (o/n): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    
    if (strtolower($line) !== 'o') {
        echo "Opération annulée.\n";
        exit(0);
    }
}

echo "🎯 Marquage des présences...\n\n";

// Initialiser le service WhatsApp
$whatsAppService = new WhatsAppService();

$successCount = 0;
$errorCount = 0;
$whatsappSuccessCount = 0;
$whatsappErrorCount = 0;

foreach ($potentialVacataires as $user) {
    try {
        // Générer une heure d'arrivée aléatoire entre 6h10 et 8h14
        $minTime = 6 * 60 + 10; // 6h10 en minutes
        $maxTime = 8 * 60 + 14; // 8h14 en minutes
        $randomMinutes = rand($minTime, $maxTime);
        
        $hours = intval($randomMinutes / 60);
        $minutes = $randomMinutes % 60;
        
        $arrivalTime = $date->copy()->setTime($hours, $minutes, rand(0, 59));
        
        // Vérifier s'il existe déjà une présence pour cet utilisateur ce jour
        $existingAttendance = StaffAttendance::where('user_id', $user->id)
            ->whereDate('attendance_date', $date)
            ->where('staff_type', 'vacataire')
            ->first();
        
        if ($existingAttendance) {
            echo "  ⚠️  {$user->name} ({$user->username}) - Déjà marqué présent\n";
            continue;
        }
        
        // Créer la présence
        $attendance = StaffAttendance::create([
            'user_id' => $user->id,
            'supervisor_id' => 1, // Admin par défaut
            'school_year_id' => $schoolYearId,
            'attendance_date' => $date->format('Y-m-d'),
            'scanned_at' => $arrivalTime,
            'scanned_qr_code' => $user->qr_code ?? 'AUTO_' . strtoupper(uniqid()),
            'is_present' => true,
            'event_type' => 'auto',
            'staff_type' => 'vacataire',
            'work_hours' => 8.0, // 8 heures par défaut
            'late_minutes' => 0,
            'early_departure_minutes' => 0,
            'notes' => 'Présence automatique générée - Personnel vacataire'
        ]);
        
        echo "  ✅ {$user->name} ({$user->username}) - Marqué présent à " . 
             $arrivalTime->format('H:i:s') . "\n";
        
        $successCount++;
        
        // Envoyer la notification WhatsApp
        if ($user->contact && strlen($user->contact) >= 8) {
            try {
                $whatsappResult = $whatsAppService->sendStaffAttendanceNotification($attendance);
                if ($whatsappResult) {
                    echo "    📱 WhatsApp envoyé au {$user->contact}\n";
                    $whatsappSuccessCount++;
                } else {
                    echo "    ⚠️  Échec WhatsApp au {$user->contact}\n";
                    $whatsappErrorCount++;
                }
            } catch (Exception $whatsappError) {
                echo "    ❌ Erreur WhatsApp: " . $whatsappError->getMessage() . "\n";
                $whatsappErrorCount++;
            }
        } else {
            echo "    ⚠️  Pas de numéro WhatsApp configuré\n";
            $whatsappErrorCount++;
        }
        
    } catch (Exception $e) {
        echo "  ❌ {$user->name} ({$user->username}) - Erreur: " . $e->getMessage() . "\n";
        $errorCount++;
    }
}

echo "\n===========================================\n";
echo "RÉSUMÉ DU TRAITEMENT\n";
echo "===========================================\n";
echo "Date traitée: $targetDate\n";
echo "Personnel traité: " . $potentialVacataires->count() . "\n";
echo "✅ Présences créées: $successCount\n";
echo "❌ Erreurs présences: $errorCount\n";
echo "📱 WhatsApp envoyés: $whatsappSuccessCount\n";
echo "📱 WhatsApp échoués: $whatsappErrorCount\n";

if ($successCount > 0) {
    echo "\n🎉 Marquage terminé avec succès!\n";
    echo "Les personnels vacataires ont été marqués présents avec des heures d'arrivée entre 6h10 et 8h14.\n";
    if ($whatsappSuccessCount > 0) {
        echo "📱 $whatsappSuccessCount notification(s) WhatsApp envoyée(s) avec succès.\n";
    }
} else {
    echo "\n⚠️ Aucune nouvelle présence créée.\n";
}

// Afficher quelques statistiques
echo "\n📊 STATISTIQUES DU JOUR\n";
echo "===========================================\n";

$totalPresences = StaffAttendance::whereDate('attendance_date', $date)->count();
$vacatairePresences = StaffAttendance::whereDate('attendance_date', $date)
    ->where('staff_type', 'vacataire')
    ->count();

echo "Total présences du jour: $totalPresences\n";
echo "Présences vacataires: $vacatairePresences\n";

echo "\n✅ Script terminé.\n";