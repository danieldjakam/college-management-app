<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Configuration
$teacherId = 62; // ANDRIENNE GUEKAM
$phone = '676515721';

echo "🔍 Récupération des informations de l'enseignant...\n\n";

// Récupérer l'enseignant
$teacher = DB::table('teachers')
    ->join('users', 'teachers.user_id', '=', 'users.id')
    ->where('teachers.id', $teacherId)
    ->select('teachers.*', 'users.name', 'users.username', 'users.email')
    ->first();

if (!$teacher) {
    echo "❌ Enseignant non trouvé!\n";
    exit(1);
}

echo "👤 Enseignant: {$teacher->name}\n";
echo "📧 Email: {$teacher->email}\n";
echo "👤 Username: {$teacher->username}\n";
echo "📱 Téléphone: {$phone}\n\n";

// Récupérer les affectations de matières
$assignments = DB::table('teacher_subjects')
    ->join('subjects', 'teacher_subjects.subject_id', '=', 'subjects.id')
    ->join('class_series', 'teacher_subjects.class_series_id', '=', 'class_series.id')
    ->join('school_classes', 'class_series.class_id', '=', 'school_classes.id')
    ->join('levels', 'school_classes.level_id', '=', 'levels.id')
    ->join('school_years', 'teacher_subjects.school_year_id', '=', 'school_years.id')
    ->where('teacher_subjects.teacher_id', $teacherId)
    ->where('teacher_subjects.is_active', true)
    ->where('school_years.is_current', true)
    ->select(
        'subjects.name as subject_name',
        'school_classes.name as class_name',
        'class_series.name as series_name',
        'levels.name as level_name'
    )
    ->get();

// Récupérer les classes où l'enseignant est professeur principal
$mainTeacherClasses = DB::table('main_teachers')
    ->join('class_series', 'main_teachers.class_series_id', '=', 'class_series.id')
    ->join('school_classes', 'class_series.class_id', '=', 'school_classes.id')
    ->join('levels', 'school_classes.level_id', '=', 'levels.id')
    ->join('school_years', 'main_teachers.school_year_id', '=', 'school_years.id')
    ->where('main_teachers.teacher_id', $teacherId)
    ->where('main_teachers.is_active', true)
    ->where('school_years.is_current', true)
    ->select(
        'school_classes.name as class_name',
        'class_series.name as series_name',
        'levels.name as level_name'
    )
    ->get();

$totalAffectations = count($assignments);
$totalMainTeacher = count($mainTeacherClasses);

echo "📚 Nombre d'affectations de matières: {$totalAffectations}\n";
echo "👨‍🏫 Professeur principal de: {$totalMainTeacher} classe(s)\n\n";

// Déterminer le titre (Cher/Chère)
$title = "Cher";
$firstName = explode(' ', $teacher->name)[0];
if (in_array(strtoupper($firstName), ['MADAME', 'MME', 'MLLE', 'MADEMOISELLE']) ||
    preg_match('/^(MADAME|MME|MLLE)/i', $teacher->name)) {
    $title = "Chère";
}

// Construire le message
$message = "🎓 RÉCAPITULATIF DES AFFECTATIONS - COLLÈGE POLYVALENT BILINGUE DE DOUALA\n\n";
$message .= "👩‍🏫 {$title} {$teacher->name},\n\n";

if ($totalAffectations > 0 || $totalMainTeacher > 0) {
    $message .= "Voici vos informations pour l'année en cours :\n\n";

    // Afficher les classes où l'enseignant est prof principal
    if ($totalMainTeacher > 0) {
        $message .= "👨‍🏫 PROFESSEUR PRINCIPAL :\n\n";
        foreach ($mainTeacherClasses as $index => $mainClass) {
            $message .= "📌 Classe : {$mainClass->class_name} {$mainClass->series_name}\n";
            $message .= "   • Niveau : {$mainClass->level_name}\n\n";
        }
    }

    // Afficher les affectations de matières
    if ($totalAffectations > 0) {
        $message .= "📚 AFFECTATIONS DE MATIÈRES :\n\n";
        foreach ($assignments as $index => $assignment) {
            $num = $index + 1;
            $message .= "📚 Affectation {$num}\n";
            $message .= "   • Matière : {$assignment->subject_name}\n";
            $message .= "   • Classe : {$assignment->class_name} {$assignment->series_name}\n";
            $message .= "   • Niveau : {$assignment->level_name}\n\n";
        }
    }

    $totalCount = $totalAffectations + $totalMainTeacher;
    $message .= "📊 Total : {$totalMainTeacher} classe(s) comme prof principal";
    if ($totalAffectations > 0) {
        $message .= " + {$totalAffectations} affectation" . ($totalAffectations > 1 ? 's' : '') . " de matière" . ($totalAffectations > 1 ? 's' : '');
    }
    $message .= "\n\n";
} else {
    $message .= "Aucune affectation n'a encore été enregistrée pour vous cette année.\n\n";
    $message .= "Veuillez contacter l'administration pour plus d'informations.\n\n";
}

$message .= "🔐 INFORMATIONS DE CONNEXION\n\n";
$message .= "🌐 Lien : http://admin.cpb-douala.com\n";
$message .= "👤 Nom d'utilisateur : {$teacher->username}\n";
$message .= "🔑 Mot de passe : password\n\n";
$message .= "📖 Vous pouvez consulter toutes vos classes et élèves sur votre tableau de bord enseignant.\n\n";
$message .= "📞 Pour toute question, contactez l'administration.\n\n";
$message .= "📱 Notification automatique du système de gestion scolaire.";

echo "📝 Message à envoyer:\n";
echo str_repeat("=", 80) . "\n";
echo $message . "\n";
echo str_repeat("=", 80) . "\n\n";

// Envoyer le message WhatsApp via Ultramsg
$instanceId = env('ULTRAMSG_INSTANCE_ID', 'instance98976');
$token = env('ULTRAMSG_TOKEN', 'sc5ej4cwqsau5afz');

$url = "https://api.ultramsg.com/{$instanceId}/messages/chat";

$params = [
    'token' => $token,
    'to' => $phone,
    'body' => $message,
    'priority' => 10
];

echo "📤 Envoi du message WhatsApp...\n\n";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => http_build_query($params),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded'
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

if ($err) {
    echo "❌ Erreur cURL: " . $err . "\n";
    exit(1);
} else {
    echo "✅ Réponse du serveur (Code HTTP: {$httpCode}):\n";
    echo $response . "\n\n";

    $responseData = json_decode($response, true);

    if ($httpCode == 200 && isset($responseData['sent']) && $responseData['sent'] === 'true') {
        echo "✅ Message envoyé avec succès à {$teacher->name} ({$phone})!\n";
    } else {
        echo "⚠️  Le message n'a peut-être pas été envoyé. Vérifiez la réponse ci-dessus.\n";
    }
}
