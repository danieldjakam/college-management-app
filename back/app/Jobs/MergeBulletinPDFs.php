<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\BulletinGeneration;
use App\Models\MergedBulletinPDF;
use setasign\Fpdi\Fpdi;

class MergeBulletinPDFs implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 600; // 10 minutes max
    public $tries = 1;

    protected $classSeriesId;
    protected $periodType;
    protected $periodIdentifier;
    protected $progressKey;

    /**
     * Create a new job instance.
     */
    public function __construct($classSeriesId, $periodType, $periodIdentifier)
    {
        $this->classSeriesId = $classSeriesId;
        $this->periodType = $periodType;
        $this->periodIdentifier = $periodIdentifier;
        $this->progressKey = "merge_pdf_{$classSeriesId}_{$periodType}_{$periodIdentifier}_" . time();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        Log::info("🔄 Début de la fusion PDF", [
            'class_series_id' => $this->classSeriesId,
            'period_type' => $this->periodType,
            'period_identifier' => $this->periodIdentifier
        ]);

        // Mettre à jour la progression
        $this->updateProgress([
            'status' => 'processing',
            'message' => 'Récupération des bulletins...',
            'percentage' => 0,
            'current' => 0,
            'total' => 0
        ]);

        try {
            // Récupérer tous les bulletins générés
            $bulletins = BulletinGeneration::where('period_type', $this->periodType)
                ->where('period_identifier', $this->periodIdentifier)
                ->whereHas('student', function($query) {
                    $query->where('class_series_id', $this->classSeriesId);
                })
                ->with('student')
                ->orderBy('created_at')
                ->get();

            if ($bulletins->isEmpty()) {
                throw new \Exception('Aucun bulletin trouvé pour cette classe et période');
            }

            Log::info("📄 {$bulletins->count()} bulletins à fusionner");

            $this->updateProgress([
                'status' => 'processing',
                'message' => "Fusion de {$bulletins->count()} bulletins...",
                'percentage' => 10,
                'current' => 0,
                'total' => $bulletins->count()
            ]);

            // Initialiser FPDI
            $pdf = new Fpdi();
            $processedCount = 0;

            foreach ($bulletins as $bulletin) {
                try {
                    $filePath = storage_path('app/' . $bulletin->file_path);

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
                    $percentage = 10 + (($processedCount / $bulletins->count()) * 80);
                    $this->updateProgress([
                        'status' => 'processing',
                        'message' => "Fusion en cours... {$processedCount}/{$bulletins->count()}",
                        'percentage' => round($percentage),
                        'current' => $processedCount,
                        'total' => $bulletins->count()
                    ]);

                } catch (\Exception $e) {
                    Log::error("❌ Erreur lors de la fusion du PDF {$bulletin->id}: " . $e->getMessage());
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
                'total' => $bulletins->count()
            ]);

            // Générer le nom du fichier
            $filename = "bulletins_{$this->periodType}_{$this->periodIdentifier}_classe_{$this->classSeriesId}_" . now()->format('Y-m-d_His') . ".pdf";
            $relativePath = "public/merged_bulletins/{$filename}";
            $fullPath = storage_path('app/' . $relativePath);

            // Créer le répertoire si nécessaire
            $directory = dirname($fullPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Sauvegarder le PDF fusionné
            $pdf->Output('F', $fullPath);

            Log::info("✅ PDF fusionné créé: {$filename}");

            // Enregistrer en base de données
            $mergedPdf = MergedBulletinPDF::create([
                'class_series_id' => $this->classSeriesId,
                'period_type' => $this->periodType,
                'period_identifier' => $this->periodIdentifier,
                'file_path' => $relativePath,
                'filename' => $filename,
                'bulletin_count' => $processedCount,
                'file_size' => filesize($fullPath),
                'status' => 'completed'
            ]);

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            Log::info("🎉 Fusion terminée en {$duration}s", [
                'bulletins_fusionnés' => $processedCount,
                'fichier' => $filename
            ]);

            // Marquer comme terminé
            $this->updateProgress([
                'status' => 'completed',
                'message' => "✅ {$processedCount} bulletins fusionnés avec succès !",
                'percentage' => 100,
                'current' => $processedCount,
                'total' => $bulletins->count(),
                'file_id' => $mergedPdf->id,
                'filename' => $filename,
                'download_url' => "/api/bulletins/download-merged/{$mergedPdf->id}",
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
