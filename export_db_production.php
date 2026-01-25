<?php
/**
 * Script d'export de base de données pour serveur de production
 * À uploader sur le serveur et exécuter via navigateur
 * URL: https://votre-site.com/export_db_production.php?key=votre_secret_key
 */

// ⚠️ SÉCURITÉ : Définir une clé secrète
define('SECRET_KEY', 'changez_moi_avec_une_cle_secrete_' . date('Ymd'));

// Vérification de la clé
if (!isset($_GET['key']) || $_GET['key'] !== SECRET_KEY) {
    die('❌ Accès refusé. Clé invalide.');
}

// Configuration (à adapter selon votre .env)
$dbHost = '127.0.0.1';
$dbName = 'c0admin';
$dbUser = 'root';  // À CHANGER
$dbPass = '';      // À CHANGER
$backupFile = 'backup_' . date('Ymd_His') . '.sql.gz';

// Temps d'exécution illimité
set_time_limit(0);
ini_set('memory_limit', '512M');

echo "🚀 Début de l'exportation...\n\n";

// Commande mysqldump
$command = sprintf(
    'mysqldump -h %s -u %s %s %s 2>&1 | gzip > %s',
    escapeshellarg($dbHost),
    escapeshellarg($dbUser),
    $dbPass ? '-p' . escapeshellarg($dbPass) : '',
    escapeshellarg($dbName),
    escapeshellarg($backupFile)
);

echo "📝 Exécution de la commande d'export...\n";
exec($command, $output, $returnCode);

if ($returnCode === 0 && file_exists($backupFile)) {
    $fileSize = filesize($backupFile);
    $fileSizeMB = round($fileSize / 1024 / 1024, 2);

    echo "✅ Export réussi!\n";
    echo "📦 Fichier: $backupFile\n";
    echo "📊 Taille: $fileSizeMB MB\n\n";

    // Téléchargement automatique
    if (isset($_GET['download'])) {
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . $backupFile . '"');
        header('Content-Length: ' . $fileSize);
        readfile($backupFile);

        // Supprimer le fichier après téléchargement (optionnel)
        if (isset($_GET['cleanup'])) {
            unlink($backupFile);
        }
        exit;
    }

    echo "🔗 <a href='?key=" . SECRET_KEY . "&download=1'>Télécharger le backup</a>\n";
    echo "🔗 <a href='?key=" . SECRET_KEY . "&download=1&cleanup=1'>Télécharger et supprimer</a>\n";
} else {
    echo "❌ Erreur lors de l'export!\n";
    echo "Code retour: $returnCode\n";
    echo "Output: " . implode("\n", $output) . "\n";
}

// ⚠️ IMPORTANT : Supprimer ce fichier après utilisation!
echo "\n\n⚠️ ATTENTION : Supprimez ce fichier après utilisation pour des raisons de sécurité!\n";
?>
