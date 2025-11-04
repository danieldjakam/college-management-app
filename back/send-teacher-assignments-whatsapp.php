<?php
/**
 * Script pour envoyer les affectations aux enseignants via WhatsApp
 * Usage: php send-teacher-assignments-whatsapp.php
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

// Liste des enseignants à notifier
$teachersToNotify = [
    ['name' => 'MOMO ELEONORE', 'phone' => '676373457'],
    ['name' => 'TANTOH GILTON LAISIN', 'phone' => '674193839'],
    ['name' => 'GUEKAM ANDRIENNE', 'phone' => '699184325'],
    ['name' => 'BILONGO\'O SARA', 'phone' => '693740710'],
    ['name' => 'LISSOTA YOTEZA', 'phone' => '694593489'],
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

    foreach ($teachersToNotify as $teacherInfo) {
        echo "---------------------------------------------------\n";
        echo "👨‍🏫 Traitement: {$teacherInfo['name']}\n";
        echo "📱 Téléphone fourni: {$teacherInfo['phone']}\n";

        // Rechercher l'enseignant par nom (logique flexible)
        $keywords = explode(' ', $teacherInfo['name']);
        $keywords = array_filter($keywords); // Remove empty strings
        echo "🔎 Recherche flexible avec les mots-clés: " . implode(', ', $keywords) . "...\n";

        $teacherQuery = Teacher::query();

        if (!empty($keywords)) {
            $teacherQuery->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%' . $keyword . '%');
                }
            });
        }
        
        $teacher = $teacherQuery->first();

        if (!$teacher) {
            echo "❌ Enseignant non trouvé dans la base de données avec le nom '{$teacherInfo['name']}'.\n\n";
            $failedCount++;
            continue;
        }

        echo "✅ Enseignant trouvé: {$teacher->first_name} {$teacher->last_name}\n";

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
            echo "⚠️ Aucune affectation trouvée pour cet enseignant\n\n";
            $failedCount++;
            continue;
        }

        echo "📚 Nombre d'affectations: {$assignments->count()}\n";

        // Formater le message selon le template de l'utilisateur
        $message = "🎓 *RÉCAPITULATIF DES AFFECTATIONS - {$schoolName}*\n\n";
        $message .= "👨‍🏫 Cher(e) *{$teacher->first_name} {$teacher->last_name}*,\n\n";
        $message .= "Voici la liste de vos affectations pour l'année scolaire *{$currentYear->name}* :\n\n";

        // Ajouter chaque affectation
        foreach ($assignments as $index => $assignment) {
            $subject = $assignment->classSeriesSubject->subject;
            $classSeries = $assignment->classSeriesSubject->classSeries;
            $schoolClass = $classSeries->schoolClass;
            $level = $schoolClass->level;

            $affectationNum = $index + 1;
            $message .= "📚 *Affectation {$affectationNum}*\n";
            $message .= "   • *Matière :* {$subject->name}\n";
            $message .= "   • *Classe :* {$classSeries->name}\n";
            $message .= "   • *Niveau :* {$level->name}\n\n";
        }

        // Ajouter les informations de connexion
        $message .= "🔐 *INFORMATIONS DE CONNEXION*\n\n";
        $message .= "🌐 *Lien :* http://admin.cpb-douala.com\n";

        // Générer ou récupérer les identifiants
        if ($teacher->user_id && $teacher->user) {
            $username = $teacher->user->username ?? $teacher->user->email;
            $password = "password123"; // Mot de passe par défaut
        } else {
            // Générer un nom d'utilisateur basique
            $firstName = strtolower(str_replace(' ', '', $teacher->first_name));
            $lastName = strtolower(str_replace(' ', '', $teacher->last_name));
            $username = "{$firstName}.{$lastName}";
            $password = "password123";
        }

        $message .= "👤 *Nom d'utilisateur :* {$username}\n";
        $message .= "🔑 *Mot de passe :* {$password}\n\n";

        $message .= "📱 *INSTRUCTIONS :*\n";
        $message .= "1️⃣ Connectez-vous à la plateforme avec vos identifiants\n";
        $message .= "2️⃣ Accédez à votre tableau de bord enseignant\n";
        $message .= "3️⃣ Consultez vos classes et élèves\n";
        $message .= "4️⃣ Commencez la saisie des notes\n\n";

        $message .= "📞 Pour toute question, contactez l'administration.\n\n";
        $message .= "🙏 Merci pour votre engagement et bonne année scolaire !\n\n";
        $message .= "---\n";
        $message .= "📧 Notification automatique du système de gestion scolaire\n";
        $message .= "🏫 {$schoolName}";

        // Afficher un aperçu du message
        echo "\n📝 Aperçu du message:\n";
        echo substr($message, 0, 200) . "...\n\n";

        // Envoyer le message via WhatsApp
        echo "📤 Envoi du message WhatsApp au numéro fourni: {$teacherInfo['phone']}...\n";

        $result = $whatsappService->sendMessage($teacherInfo['phone'], $message);

        if ($result) {
            echo "✅ Message envoyé avec succès à {$teacherInfo['name']} au {$teacherInfo['phone']}\n";
            $successCount++;
        } else {
            echo "❌ Échec de l'envoi à {$teacherInfo['name']} au {$teacherInfo['phone']}\n";
            echo "   Vérifiez les logs pour plus de détails\n";
            $failedCount++;
        }

        echo "\n";

        // Petite pause entre chaque envoi pour éviter de surcharger l'API
        if ($index < count($teachersToNotify) - 1) {
            sleep(2);
        }
    }

    // Résumé final
    echo "=====================================================\n";
    echo "📊 RÉSUMÉ DE L'ENVOI\n";
    echo "=====================================================\n";
    echo "✅ Envois réussis: {$successCount}\n";
    echo "❌ Envois échoués: {$failedCount}\n";
    echo "📱 Total traité: " . count($teachersToNotify) . "\n\n";

    if ($successCount > 0) {
        echo "🎉 Les notifications WhatsApp ont été envoyées avec succès!\n";
    }

    if ($failedCount > 0) {
        echo "⚠️ Certains envois ont échoué. Consultez les logs ci-dessus pour les détails.\n";
    }

} catch (\Exception $e) {
    echo "❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . "\n";
    echo "📍 Ligne: " . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✨ Script terminé\n";
