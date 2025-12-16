<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\MergedHonorRollPDF;
use setasign\Fpdi\Fpdi;

class MergeHonorRollPDFs implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 600; // 10 minutes max
    public $tries = 1;

    protected $trimesterId;
    protected $sectionId;
    protected $levelId;
    protected $classId;
    protected $seriesId;
    protected $certificatePaths;
    protected $progressKey;
    protected $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct($trimesterId, $sectionId, $levelId, $classId, $seriesId, $certificatePaths, $jobId = null)
    {
        $this->trimesterId = $trimesterId;
        $this->sectionId = $sectionId;
        $this->levelId = $levelId;
        $this->classId = $classId;
        $this->seriesId = $seriesId;
        $this->certificatePaths = $certificatePaths;
        $this->jobId = $jobId ?? uniqid('merge_honor_', true);
        $this->progressKey = "merge_honor_progress_{$this->jobId}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        Log::info("🔄 Début de la fusion des certificats d'honneur", [
            'trimester_id' => $this->trimesterId,
            'series_id' => $this->seriesId,
            'certificate_count' => count($this->certificatePaths)
        ]);

        // Mettre à jour la progression
        $this->updateProgress([
            'status' => 'processing',
            'message' => 'Récupération des certificats...',
            'percentage' => 0,
            'current' => 0,
            'total' => count($this->certificatePaths)
        ]);

        try {
            if (empty($this->certificatePaths)) {
                throw new \Exception('Aucun certificat à fusionner');
            }

            Log::info("📄 " . count($this->certificatePaths) . " certificats à fusionner");

            $this->updateProgress([
                'status' => 'processing',
                'message' => "Fusion de " . count($this->certificatePaths) . " certificats...",
                'percentage' => 10,
                'current' => 0,
                'total' => count($this->certificatePaths)
            ]);

            // Initialiser FPDI
            $pdf = new Fpdi();
            $processedCount = 0;

            foreach ($this->certificatePaths as $certificatePath) {
                try {
                    $filePath = storage_path('app/' . $certificatePath);

                    if (!file_exists($filePath)) {
                        Log::warning("⚠️ Fichier PDF introuvable: {$filePath}");
                        continue;
                    }

                    // Compter le nombre de pages du PDF source
                    $pageCount = $pdf->setSourceFile($filePath);

                    // Importer toutes les pages
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $tplId = $pdf->importPage($pageNo);
                        $size = $pdf->getTemplateSize($tplId);

                        // Ajouter une page avec les dimensions du template
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($tplId);
                    }

                    $processedCount++;

                    // Mettre à jour la progression
                    $percentage = 10 + (($processedCount / count($this->certificatePaths)) * 80);
                    $this->updateProgress([
                        'status' => 'processing',
                        'message' => "Fusion en cours... {$processedCount}/" . count($this->certificatePaths),
                        'percentage' => round($percentage),
                        'current' => $processedCount,
                        'total' => count($this->certificatePaths)
                    ]);

                } catch (\Exception $e) {
                    Log::error("❌ Erreur lors de la fusion du certificat: " . $e->getMessage());
                }
            }

            if ($processedCount === 0) {
                throw new \Exception('Aucun PDF n\'a pu être fusionné');
            }

            $this->updateProgress([
                'status' => 'processing',
                'message' => 'Génération du fichier final...',
                'percentage' => 90,
                'current' => $processedCount,
                'total' => count($this->certificatePaths)
            ]);

            // Générer le nom du fichier
            $filterLabel = $this->generateFilterLabel();
            $filename = "honor_rolls_trim{$this->trimesterId}_{$filterLabel}_" . now()->format('Y-m-d_His') . ".pdf";
            $relativePath = "merged_honor_rolls/{$filename}";
            $fullPath = storage_path('app/public/' . $relativePath);

            // Créer le répertoire si nécessaire
            $directory = dirname($fullPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Sauvegarder le PDF fusionné
            $pdf->Output('F', $fullPath);

            Log::info("✅ PDF fusionné créé: {$filename}");

            // Enregistrer en base de données
            $mergedPdf = MergedHonorRollPDF::create([
                'trimester_id' => $this->trimesterId,
                'section_id' => $this->sectionId,
                'level_id' => $this->levelId,
                'class_id' => $this->classId,
                'series_id' => $this->seriesId,
                'file_path' => $relativePath,
                'filename' => $filename,
                'certificate_count' => $processedCount,
                'file_size' => filesize($fullPath),
                'status' => 'completed'
            ]);

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            Log::info("🎉 Fusion terminée en {$duration}s", [
                'certificats_fusionnés' => $processedCount,
                'fichier' => $filename
            ]);

            // Marquer comme terminé
            $this->updateProgress([
                'status' => 'completed',
                'message' => "✅ {$processedCount} certificats fusionnés avec succès !",
                'percentage' => 100,
                'current' => $processedCount,
                'total' => count($this->certificatePaths),
                'file_id' => $mergedPdf->id,
                'filename' => $filename,
                'download_url' => "/api/honor-rolls/merged/{$mergedPdf->id}/download",
                'duration' => $duration
            ], 3600); // Garder 1 heure

        } catch (\Exception $e) {
            Log::error("❌ Erreur fatale lors de la fusion PDF: " . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);

            $this->updateProgress([
                'status' => 'failed',
                'message' => "❌ Erreur: " . $e->getMessage(),
                'percentage' => 0,
                'error' => $e->getMessage()
            ], 3600);

            throw $e;
        }
    }

    /**
     * Générer un label pour les filtres appliqués
     */
    protected function generateFilterLabel()
    {
        if ($this->seriesId) {
            return "serie_{$this->seriesId}";
        } elseif ($this->classId) {
            return "class_{$this->classId}";
        } elseif ($this->levelId) {
            return "level_{$this->levelId}";
        } elseif ($this->sectionId) {
            return "section_{$this->sectionId}";
        }
        return "all";
    }

    /**
     * Mettre à jour la progression dans le cache
     */
    protected function updateProgress(array $data, int $ttl = 600)
    {
        \Cache::put($this->progressKey, $data, $ttl);
    }

    /**
     * Récupérer la clé de progression
     */
    public function getProgressKey()
    {
        return $this->progressKey;
    }
}
