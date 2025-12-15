<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BulletinService;
use Barryvdh\DomPDF\Facade\Pdf;

echo "=== GÉNÉRATION PDF COMPLÈTE POUR GERMAINE ===\n\n";

$service = new BulletinService();

// GERMAINE
$student = DB::table('students')
    ->where('first_name', 'LIKE', '%GERMAINE%')
    ->where('last_name', 'LIKE', '%EMADE%')
    ->first();

if (!$student) {
    echo "❌ Étudiante GERMAINE non trouvée\n";
    exit;
}

echo "Étudiante: {$student->first_name} {$student->last_name} (ID: {$student->id})\n\n";

// Générer les données du bulletin Trimestre 1
echo "1. Génération des données...\n";
$bulletinData = $service->generateTrimesterBulletinData(1, $student->id);

if (!$bulletinData) {
    echo "❌ Échec de génération des données\n";
    exit;
}

// Vérifier les données ANGLAIS
$anglais = collect($bulletinData['subjects'])->firstWhere('name', 'Anglais');
if ($anglais) {
    echo "   Anglais - Seq1: {$anglais['sequence1']}, Seq2: {$anglais['sequence2']}, Comp: {$anglais['composition']}\n\n";
}

// Générer le HTML
echo "2. Génération HTML (mode PDF)...\n";
$html = $service->renderBulletinTemplate('trimester', $bulletinData, true);

// Sauvegarder le HTML
$htmlPath = '/tmp/bulletin_germaine_final.html';
file_put_contents($htmlPath, $html);
echo "   HTML sauvegardé dans: {$htmlPath}\n";

// Vérifier le HTML généré
if (preg_match('/ANGLAIS<\/td><td[^>]*>([^<]+)<\/td><td[^>]*>([^<]+)<\/td><td[^>]*>([^<]+)<\/td>/', $html, $matches)) {
    echo "   Valeurs dans HTML: Seq1={$matches[1]}, Seq2={$matches[2]}, Comp={$matches[3]}\n\n";
} else {
    echo "   ⚠️  Impossible d'extraire les valeurs du HTML\n\n";
}

// Générer le PDF
echo "3. Conversion HTML → PDF...\n";
try {
    $pdf = Pdf::loadHTML($html);
    $pdf->setPaper('A4', 'portrait');

    $pdfPath = '/tmp/bulletin_germaine_test.pdf';
    $pdf->save($pdfPath);

    echo "   ✅ PDF généré avec succès: {$pdfPath}\n";
    echo "\n📄 Ouvrez le PDF pour vérifier si les notes s'affichent correctement.\n";

    // Ouvrir automatiquement le PDF
    exec("open '{$pdfPath}'");

} catch (Exception $e) {
    echo "   ❌ Erreur lors de la génération du PDF: " . $e->getMessage() . "\n";
}
