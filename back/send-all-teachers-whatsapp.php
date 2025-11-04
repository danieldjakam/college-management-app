<?php
/**
 * Script pour envoyer les affectations à tous les enseignants via WhatsApp
 * Usage: php send-all-teachers-whatsapp.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Charger les configurations Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// WORKAROUND: Force database credentials to bypass environment issues
config([
    'database.connections.mysql.host' => '127.0.0.1',
    'database.connections.mysql.database' => 'c0admin',
    'database.connections.mysql.username' => 'root',
    'database.connections.mysql.password' => '',
]);

use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\SchoolYear;
use App\Models\SchoolSetting;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;

echo "🎓 ENVOI DES AFFECTATIONS AUX ENSEIGNANTS VIA WHATSAPP\n";
echo "=====================================================\n\n";

// Liste complète des enseignants avec leurs numéros WhatsApp
$teachersToNotify = [
    ['name' => 'WOULINA Marcel', 'phone' => '658047838'],
    ['name' => 'MAWOWO ERICA', 'phone' => '651239216'],
    ['name' => 'KAMENNI MONIS NESTOR', 'phone' => '670403323'],
    ['name' => 'NANGUE AROL', 'phone' => '692142243'],
    ['name' => 'BOUM GWETH LAURENT', 'phone' => '699824521'],
    ['name' => 'TATSINKOU TENE CYRILLE', 'phone' => '697957200'],
    ['name' => 'CLADORE TCHANDO', 'phone' => '683129163'],
    ['name' => 'NANPASSI MASSAMBA OSCAR', 'phone' => '654160846'],
    ['name' => 'NTEM IV PAUL', 'phone' => '699734094'],
    ['name' => 'NDONDOCK NICAISE', 'phone' => '674385186'],
    ['name' => 'AURELIEN TALLA', 'phone' => '674831332'],
    ['name' => 'NKONGHO TAMBE', 'phone' => '620390166'],
    ['name' => 'OLONOU ALVINE NINA', 'phone' => '697339007'],
    ['name' => 'Binong joseph Rene', 'phone' => '675314058'],
    ['name' => 'Djomatchoua Noubissie sandrine', 'phone' => '674536333'],
    ['name' => 'LISSOTA YOMZAK', 'phone' => '694593489'],
    ['name' => 'STEPHANE EVINDI FRANCK', 'phone' => '677999281'],
    ['name' => 'PETSAM LIENOU SERENA INGRID', 'phone' => '698886931'],
    ['name' => 'NONO Gilles', 'phone' => '696172515'],
];

try {
    // Récupérer l'année scolaire courante
    $currentYear = SchoolYear::where('is_current', true)->first();
    if (!$currentYear) {
        echo "❌ Erreur: Aucune année scolaire courante trouvée\n";
        exit(1);
    }

    echo "📅 Année scolaire: {$currentYear->name}\n\n";

    // Récupérer les paramètres de l'école
    $schoolSettings = SchoolSetting::getSettings();
    $schoolName = $schoolSettings->school_name ?? 'COLLÈGE POLYVALENT BILINGUE DE DOUALA';

    // Initialiser le service WhatsApp
    $whatsappService = new WhatsAppService();

    // Vérifier la configuration WhatsApp
    echo "🔧 Vérification de la configuration WhatsApp...\n";
    $configTest = $whatsappService->testConfiguration();
    if (!$configTest['success']) {
        echo "⚠️ AVERTISSEMENT: " . $configTest['message'] . "\n";
        echo "   Le script continuera en mode simulation.\n\n";
    } else {
        echo "✅ Configuration WhatsApp opérationnelle\n\n";
    }

    $successCount = 0;
    $failedCount = 0;
    $notFoundCount = 0;

    foreach ($teachersToNotify as $index => $teacherInfo) {
        echo "---------------------------------------------------\n";
        echo "👨‍🏫 Enseignant " . ($index + 1) . "/" . count($teachersToNotify) . "\n";
        echo "📝 Nom: {$teacherInfo['name']}\n";
        echo "📱 Téléphone: {$teacherInfo['phone']}\n";

        // Rechercher l'enseignant par nom (logique flexible)
        $nameParts = preg_split('/\s+/', trim($teacherInfo['name']));
        $nameParts = array_filter($nameParts);

        echo "🔎 Recherche avec les mots-clés: " . implode(', ', $nameParts) . "...\n";

        // Essayer différentes stratégies de recherche
        $teacher = null;

        // Stratégie 1: Recherche exacte avec CONCAT
        $teacherQuery = Teacher::whereRaw(
            "CONCAT(LOWER(first_name), ' ', LOWER(last_name)) LIKE ?",
            ['%' . strtolower($teacherInfo['name']) . '%']
        )->first();

        if ($teacherQuery) {
            $teacher = $teacherQuery;
        }

        // Stratégie 2: Recherche par parties du nom
        if (!$teacher && count($nameParts) >= 2) {
            $teacher = Teacher::where(function($query) use ($nameParts) {
                foreach ($nameParts as $part) {
                    $query->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%' . $part . '%');
                }
            })->first();
        }

        // Stratégie 3: Recherche par le premier et dernier mot
        if (!$teacher && count($nameParts) >= 2) {
            $firstName = $nameParts[0];
            $lastName = end($nameParts);

            $teacher = Teacher::where(function($query) use ($firstName, $lastName) {
                $query->where('first_name', 'like', '%' . $firstName . '%')
                      ->orWhere('last_name', 'like', '%' . $lastName . '%');
            })->first();
        }

        // Stratégie 4: Recherche par numéro de téléphone
        if (!$teacher) {
            $phone = preg_replace('/\D/', '', $teacherInfo['phone']);
            $teacher = Teacher::where('phone_number', 'like', '%' . substr($phone, -8) . '%')->first();
        }

        if (!$teacher) {
            echo "❌ Enseignant NON TROUVÉ dans la base de données\n";
            echo "   Vérifiez l'orthographe du nom ou le numéro de téléphone\n\n";
            $notFoundCount++;
            $failedCount++;
            continue;
        }

        echo "✅ Enseignant trouvé: {$teacher->first_name} {$teacher->last_name}\n";
        echo "   ID: {$teacher->id}\n";

        // Vérifier et mettre à jour le numéro de téléphone si nécessaire
        if (empty($teacher->phone_number) || $teacher->phone_number != $teacherInfo['phone']) {
            echo "📞 Mise à jour du numéro de téléphone: {$teacherInfo['phone']}\n";
            $teacher->phone_number = $teacherInfo['phone'];
            $teacher->save();
        }

        // Récupérer toutes les affectations de l'enseignant pour l'année courante
        $assignments = TeacherAssignment::where('teacher_id', $teacher->id)
            ->where('school_year_id', $currentYear->id)
            ->where('is_active', true)
            ->with([
                'classSeriesSubject.subject',
                'classSeriesSubject.classSeries.schoolClass.level'
            ])
            ->get();

        if ($assignments->isEmpty()) {
            echo "⚠️ Aucune affectation trouvée pour cet enseignant\n";
            echo "   L'enseignant sera quand même notifié avec ses identifiants de connexion\n";
        } else {
            echo "📚 Nombre d'affectations: {$assignments->count()}\n";
        }

        // Formater le message selon le template fourni
        $message = "🎓 *RÉCAPITULATIF DES AFFECTATIONS - {$schoolName}*\n\n";

        // Déterminer le titre de civilité
        $title = ($teacher->gender == 'female') ? 'Chère' : 'Cher';
        $message .= "👩‍🏫 {$title} *{$teacher->first_name} {$teacher->last_name}*,\n\n";

        if ($assignments->isEmpty()) {
            $message .= "Vous n'avez actuellement aucune affectation pour l'année scolaire *{$currentYear->name}*.\n\n";
            $message .= "Veuillez contacter l'administration pour plus d'informations.\n\n";
        } else {
            $message .= "Voici la liste complète de vos affectations pour l'année en cours :\n\n";

            // Ajouter chaque affectation
            foreach ($assignments as $idx => $assignment) {
                $subject = $assignment->classSeriesSubject->subject;
                $classSeries = $assignment->classSeriesSubject->classSeries;
                $schoolClass = $classSeries->schoolClass;
                $level = $schoolClass->level;

                $affectationNum = $idx + 1;
                $message .= "📚 *Affectation {$affectationNum}*\n";
                $message .= "   • *Matière :* {$subject->name}\n";
                $message .= "   • *Classe :* {$classSeries->name}\n";
                $message .= "   • *Niveau :* {$level->name}\n\n";
            }

            $message .= "📊 *Total :* {$assignments->count()} affectation" . ($assignments->count() > 1 ? 's' : '') . "\n\n";
        }

        // Ajouter les informations de connexion
        $message .= "🔐 *INFORMATIONS DE CONNEXION*\n\n";
        $message .= "🌐 *Lien :* http://admin.cpb-douala.com\n";

        // Générer ou récupérer les identifiants
        if ($teacher->user_id && $teacher->user) {
            $username = $teacher->user->username ?? $teacher->user->email;
            $password = "password123"; // Mot de passe par défaut (à personnaliser si besoin)
        } else {
            // Générer un nom d'utilisateur
            $firstName = strtolower(preg_replace('/[^a-zA-Z]/', '', $teacher->first_name));
            $lastName = strtolower(preg_replace('/[^a-zA-Z]/', '', $teacher->last_name));
            $username = substr($firstName, 0, 1) . $lastName . '_' . $teacher->id;
            $password = "password123";
        }

        $message .= "👤 *Nom d'utilisateur :* {$username}\n";
        $message .= "🔑 *Mot de passe :* {$password}\n\n";

        $message .= "📖 Vous pouvez consulter toutes vos classes et élèves sur votre tableau de bord enseignant.\n\n";
        $message .= "📞 Pour toute question, contactez l'administration.\n\n";
        $message .= "📱 Notification automatique du système de gestion scolaire.";

        // Afficher un aperçu du message
        echo "\n📝 Aperçu du message (premiers 250 caractères):\n";
        echo str_replace('*', '', substr($message, 0, 250)) . "...\n\n";

        // Envoyer le message via WhatsApp
        echo "📤 Envoi du message WhatsApp au {$teacherInfo['phone']}...\n";

        $result = $whatsappService->sendMessage($teacherInfo['phone'], $message);

        if ($result) {
            echo "✅ Message envoyé avec succès!\n";
            $successCount++;
        } else {
            echo "❌ Échec de l'envoi du message\n";
            echo "   Vérifiez les logs pour plus de détails\n";
            $failedCount++;
        }

        echo "\n";

        // Pause entre chaque envoi (2 secondes) pour éviter de surcharger l'API
        if ($index < count($teachersToNotify) - 1) {
            echo "⏳ Pause de 2 secondes avant le prochain envoi...\n\n";
            sleep(2);
        }
    }

    // Résumé final
    echo "=====================================================\n";
    echo "📊 RÉSUMÉ DE L'ENVOI\n";
    echo "=====================================================\n";
    echo "✅ Envois réussis: {$successCount}\n";
    echo "❌ Envois échoués: {$failedCount}\n";
    echo "🔍 Enseignants non trouvés: {$notFoundCount}\n";
    echo "📱 Total traité: " . count($teachersToNotify) . "\n\n";

    if ($successCount > 0) {
        echo "🎉 Les notifications WhatsApp ont été envoyées avec succès!\n";
    }

    if ($failedCount > 0) {
        echo "⚠️ Certains envois ont échoué. Consultez les logs ci-dessus pour les détails.\n";
    }

    if ($notFoundCount > 0) {
        echo "\n⚠️ ATTENTION: {$notFoundCount} enseignant(s) n'ont pas été trouvés dans la base de données.\n";
        echo "   Vérifiez l'orthographe des noms ou ajoutez-les d'abord dans le système.\n";
    }

} catch (\Exception $e) {
    echo "❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . "\n";
    echo "📍 Ligne: " . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✨ Script terminé\n";
