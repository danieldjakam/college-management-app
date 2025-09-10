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
use App\Models\Teacher;
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

// Rechercher les enseignants vacataires
echo "\n🔍 Recherche des enseignants vacataires...\n";

// Liste des noms spécifiques à traiter avec heures fixes
$nomsCibles = [
    'Jacqueline' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Yagai' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Tizi' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Ouandji' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Libon' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Math' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Mba' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Dickson' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Kwamo' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'NNoho' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Djioleu' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Nkongho' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Tambe' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Gabriel' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'NGO' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Samnik' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Heuyo' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Patrice' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Atemnkeng' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Fotsop' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Pamowa' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Lekeaka' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Prince' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Beti' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Joseiphine' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Josephine' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Odele' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Fokou' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Gisele' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Lokio' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Lionie' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'NGueyon' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Hubert' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'TCHANANG' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'ADIBONE' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'HUGUETTE' => ['heure_entree' => '07:30:00', 'heure_sortie' => null],
    'Mirable' => ['heure_entree' => '07:30:00', 'heure_sortie' => '10:00:00'], // Cas spécial : entrée ET sortie
];

// Chercher les enseignants correspondant aux noms dans la liste
$vacataireTeachers = collect();

foreach ($nomsCibles as $nom => $horaires) {
    $teachers = Teacher::where('type_personnel', 'V')
        ->where('is_active', 1)
        ->where(function($query) use ($nom) {
            $query->where('first_name', 'LIKE', "%{$nom}%")
                  ->orWhere('last_name', 'LIKE', "%{$nom}%");
        })
        ->with('user')
        ->get();
    
    foreach ($teachers as $teacher) {
        if ($teacher->user) {
            echo "✅ Trouvé: {$teacher->first_name} {$teacher->last_name} pour '{$nom}'\n";
            $teacher->horaires_fixes = $horaires;
            $vacataireTeachers->push($teacher);
        }
    }
}

echo "Enseignants vacataires ciblés trouvés: " . $vacataireTeachers->count() . " personnes\n";

// Convertir en collection d'utilisateurs pour compatibilité avec le reste du script
$potentialVacataires = $vacataireTeachers->map(function($teacher) {
    $user = $teacher->user;
    $user->horaires_fixes = $teacher->horaires_fixes; // Transférer les horaires
    return $user;
})->filter(); // Supprimer les null (enseignants sans compte utilisateur)

echo "Personnel vacataire trouvé: " . $potentialVacataires->count() . " personnes\n\n";

if ($potentialVacataires->isEmpty()) {
    echo "❌ Aucun personnel à traiter\n";
    exit(1);
}

// Vérifier s'il y a déjà des présences pour cette date
$existingAttendances = StaffAttendance::whereDate('attendance_date', $date)
    ->where('staff_type', 'teacher')
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
        // Utiliser l'heure fixe (7h30) au lieu d'une heure aléatoire
        $heureEntree = $user->horaires_fixes['heure_entree'];
        list($hour, $minute, $second) = explode(':', $heureEntree);
        
        $arrivalTime = $date->copy()->setTime((int)$hour, (int)$minute, (int)$second);
        
        // Vérifier s'il existe déjà une présence pour cet utilisateur ce jour
        $existingAttendance = StaffAttendance::where('user_id', $user->id)
            ->whereDate('attendance_date', $date)
            ->where('staff_type', 'teacher')
            ->first();
        
        if ($existingAttendance) {
            echo "  ⚠️  {$user->name} ({$user->username}) - Déjà marqué présent\n";
            continue;
        }
        
        // Créer la présence d'entrée
        $attendance = StaffAttendance::create([
            'user_id' => $user->id,
            'supervisor_id' => 1, // Admin par défaut
            'school_year_id' => $schoolYearId,
            'attendance_date' => $date->format('Y-m-d'),
            'scanned_at' => $arrivalTime,
            'scanned_qr_code' => $user->qr_code ?? 'AUTO_' . strtoupper(uniqid()),
            'is_present' => true,
            'event_type' => 'entry',
            'staff_type' => 'teacher',
            'work_hours' => 8.0,
            'late_minutes' => 0,
            'early_departure_minutes' => 0,
            'notes' => 'Présence automatique générée - Enseignant vacataire (ENTRÉE)'
        ]);
        
        echo "  ✅ {$user->name} ({$user->username}) - Marqué présent à " . 
             $arrivalTime->format('H:i:s') . "\n";
        
        $successCount++;
        
        // Envoyer la notification WhatsApp pour l'entrée
        if ($user->contact && strlen($user->contact) >= 8) {
            try {
                $whatsappResult = $whatsAppService->sendStaffAttendanceNotification($attendance);
                if ($whatsappResult) {
                    echo "    📱 WhatsApp entrée envoyé au {$user->contact}\n";
                    $whatsappSuccessCount++;
                } else {
                    echo "    ⚠️  Échec WhatsApp entrée au {$user->contact}\n";
                    $whatsappErrorCount++;
                }
            } catch (Exception $whatsappError) {
                echo "    ❌ Erreur WhatsApp entrée: " . $whatsappError->getMessage() . "\n";
                $whatsappErrorCount++;
            }
        } else {
            echo "    ⚠️  Pas de numéro WhatsApp configuré\n";
            $whatsappErrorCount++;
        }
        
        // Si l'utilisateur a une heure de sortie (cas de Mirable), créer aussi la sortie
        if ($user->horaires_fixes['heure_sortie']) {
            $heureSortie = $user->horaires_fixes['heure_sortie'];
            list($exitHour, $exitMinute, $exitSecond) = explode(':', $heureSortie);
            $exitTime = $date->copy()->setTime((int)$exitHour, (int)$exitMinute, (int)$exitSecond);
            
            $exitAttendance = StaffAttendance::create([
                'user_id' => $user->id,
                'supervisor_id' => 1,
                'school_year_id' => $schoolYearId,
                'attendance_date' => $date->format('Y-m-d'),
                'scanned_at' => $exitTime,
                'scanned_qr_code' => $user->qr_code ?? 'AUTO_' . strtoupper(uniqid()),
                'is_present' => true,
                'event_type' => 'exit',
                'staff_type' => 'teacher',
                'work_hours' => 2.5, // 2h30 de travail (7h30-10h00)
                'late_minutes' => 0,
                'early_departure_minutes' => 0,
                'notes' => 'Sortie automatique générée - Enseignant vacataire (SORTIE)'
            ]);
            
            echo "    🚪 Sortie ajoutée à " . $exitTime->format('H:i:s') . "\n";
            $successCount++;
            
            // Envoyer notification WhatsApp pour la sortie
            if ($user->contact && strlen($user->contact) >= 8) {
                try {
                    $whatsappResult = $whatsAppService->sendStaffAttendanceNotification($exitAttendance);
                    if ($whatsappResult) {
                        echo "    📱 WhatsApp sortie envoyé au {$user->contact}\n";
                        $whatsappSuccessCount++;
                    } else {
                        echo "    ⚠️  Échec WhatsApp sortie au {$user->contact}\n";
                        $whatsappErrorCount++;
                    }
                } catch (Exception $whatsappError) {
                    echo "    ❌ Erreur WhatsApp sortie: " . $whatsappError->getMessage() . "\n";
                    $whatsappErrorCount++;
                }
            }
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
    ->where('staff_type', 'teacher')
    ->whereIn('user_id', Teacher::where('type_personnel', 'V')->pluck('user_id'))
    ->count();

echo "Total présences du jour: $totalPresences\n";
echo "Présences vacataires: $vacatairePresences\n";

echo "\n✅ Script terminé.\n";