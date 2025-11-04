<?php
/**
 * Script pour envoyer l'affectation au dernier enseignant via WhatsApp
 * Usage: php send-final-teacher-whatsapp.php
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

echo "🎓 ENVOI AFFECTATION AU DERNIER ENSEIGNANT\n";
echo "==========================================\n\n";

// Dernier enseignant
$teacherInfo = ['name' => 'OSCAR MAMPASSI', 'phone' => '654160846'];

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

    echo "---------------------------------------------------\n";
    echo "📝 Nom: {$teacherInfo['name']}\n";
    echo "📱 Téléphone: {$teacherInfo['phone']}\n";

    // Rechercher l'enseignant directement par ID
    $teacher = Teacher::find(17); // ID: 17 selon les logs

    if (!$teacher) {
        echo "❌ Enseignant non trouvé\n";
        exit(1);
    }

    echo "✅ Enseignant trouvé: {$teacher->first_name} {$teacher->last_name}\n";
    echo "   ID: {$teacher->id}\n";

    // Mettre à jour le numéro de téléphone
    echo "📞 Mise à jour du numéro de téléphone: {$teacherInfo['phone']}\n";
    $teacher->phone_number = $teacherInfo['phone'];
    $teacher->save();

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
        $password = "password123";
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
    echo "\n📝 Aperçu du message (premiers 300 caractères):\n";
    echo str_replace('*', '', substr($message, 0, 300)) . "...\n\n";

    // Envoyer le message via WhatsApp
    echo "📤 Envoi du message WhatsApp au {$teacherInfo['phone']}...\n";

    $result = $whatsappService->sendMessage($teacherInfo['phone'], $message);

    if ($result) {
        echo "✅ Message envoyé avec succès!\n";
    } else {
        echo "❌ Échec de l'envoi du message\n";
    }

} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✨ Script terminé\n";
