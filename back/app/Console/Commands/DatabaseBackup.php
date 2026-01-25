<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup
                            {--compress : Compresser le backup avec gzip}
                            {--download : Générer un lien de téléchargement}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer une sauvegarde de la base de données';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Début de la sauvegarde de la base de données...');

        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $timestamp = date('Ymd_His');
        $filename = "backup_{$dbName}_{$timestamp}.sql";
        $compress = $this->option('compress');

        if ($compress) {
            $filename .= '.gz';
        }

        $backupPath = storage_path("app/backups/{$filename}");

        // Créer le dossier backups s'il n'existe pas
        if (!is_dir(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }

        // Construire la commande mysqldump
        $command = sprintf(
            'mysqldump -h %s -P %s -u %s %s %s %s %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            $dbPass ? '-p' . escapeshellarg($dbPass) : '',
            '--single-transaction',
            '--skip-comments',
            escapeshellarg($dbName)
        );

        if ($compress) {
            $command .= ' | gzip -9';
        }

        $command .= ' > ' . escapeshellarg($backupPath);

        $this->info("📝 Exportation en cours...");

        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode === 0 && file_exists($backupPath)) {
            $fileSize = filesize($backupPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            $this->info("✅ Sauvegarde créée avec succès!");
            $this->line("📦 Fichier: {$filename}");
            $this->line("📊 Taille: {$fileSizeMB} MB");
            $this->line("📁 Chemin: {$backupPath}");

            // Nettoyer les vieux backups (garder seulement les 5 derniers)
            $this->cleanOldBackups();

            if ($this->option('download')) {
                $this->generateDownloadLink($filename);
            }

            return Command::SUCCESS;
        } else {
            $this->error("❌ Erreur lors de la sauvegarde!");
            $this->error("Code retour: {$returnCode}");
            if (!empty($output)) {
                $this->error("Output: " . implode("\n", $output));
            }
            return Command::FAILURE;
        }
    }

    /**
     * Nettoyer les vieux backups
     */
    protected function cleanOldBackups()
    {
        $backupDir = storage_path('app/backups');
        $files = glob($backupDir . '/backup_*.sql*');

        // Trier par date de modification (plus récent en premier)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Garder seulement les 5 plus récents
        $toDelete = array_slice($files, 5);

        foreach ($toDelete as $file) {
            unlink($file);
            $this->line("🗑️  Ancien backup supprimé: " . basename($file));
        }
    }

    /**
     * Générer un lien de téléchargement temporaire
     */
    protected function generateDownloadLink($filename)
    {
        $this->info("\n📥 Pour télécharger le backup:");
        $this->line("1. Créez une route temporaire dans routes/web.php:");
        $this->line("");
        $this->line("Route::get('/download-backup/{filename}', function(\$filename) {");
        $this->line("    \$path = storage_path('app/backups/' . \$filename);");
        $this->line("    return response()->download(\$path);");
        $this->line("})->middleware('auth');");
        $this->line("");
        $this->line("2. Accédez à: " . url("/download-backup/{$filename}"));
        $this->line("");
        $this->warn("⚠️  N'oubliez pas de supprimer cette route après téléchargement!");
    }
}
