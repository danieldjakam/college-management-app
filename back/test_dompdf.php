<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

echo "Test Dompdf...\n";

$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test</title>
    <style>
        body { font-family: Arial; }
        h1 { color: blue; }
    </style>
</head>
<body>
    <h1>Test Dompdf</h1>
    <p>Ceci est un test simple.</p>
</body>
</html>';

try {
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $output = $dompdf->output();
    $pdfFile = __DIR__ . '/test_simple.pdf';
    file_put_contents($pdfFile, $output);

    echo "PDF créé: $pdfFile\n";
    echo "Taille: " . filesize($pdfFile) . " bytes\n";

} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}

?>
