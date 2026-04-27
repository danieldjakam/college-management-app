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

class MergeBulletinPDFs implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 600;
    public $tries = 1;

    protected $classSeriesId;
    protected $periodType;
    protected $periodIdentifier;
    protected $progressKey;
    protected $jobId;

    public function __construct($classSeriesId, $periodType, $periodIdentifier, $jobId = null)
    {
        $this->classSeriesId = $classSeriesId;
        $this->periodType = $periodType;
        $this->periodIdentifier = $periodIdentifier;
        $this->jobId = $jobId ?? uniqid('merge_', true);
        $this->progressKey = "merge_progress_{$this->jobId}";
    }

    public function handle(): void
    {
        $startTime = microtime(true);

        Log::info("Debut de la fusion PDF", [
            'class_series_id' => $this->classSeriesId,
            'period_type' => $this->periodType,
            'period_identifier' => $this->periodIdentifier
        ]);

        $this->updateProgress([
            'status' => 'processing',
            'message' => 'Recuperation des bulletins...',
            'percentage' => 0,
            'current' => 0,
            'total' => 0
        ]);

        try {
            $bulletins = BulletinGeneration::where('period_type', $this->periodType)
                ->where('period_identifier', $this->periodIdentifier)
                ->whereHas('student', function($query) {
                    $query->where('class_series_id', $this->classSeriesId);
                })
                ->with('student')
                ->orderBy('created_at')
                ->get();

            if ($bulletins->isEmpty()) {
                throw new \Exception('Aucun bulletin trouve pour cette classe et periode');
            }

            Log::info("{$bulletins->count()} bulletins a fusionner");

            $this->updateProgress([
                'status' => 'processing',
                'message' => "Fusion de {$bulletins->count()} bulletins...",
                'percentage' => 10,
                'current' => 0,
                'total' => $bulletins->count()
            ]);

            // Collecter les fichiers PDF existants
            $pdfFiles = [];
            foreach ($bulletins as $bulletin) {
                $filePath = storage_path('app/' . $bulletin->file_path);
                if (file_exists($filePath)) {
                    $pdfFiles[] = $filePath;
                } else {
                    Log::warning("Fichier PDF introuvable: {$filePath}");
                }
            }

            if (empty($pdfFiles)) {
                throw new \Exception('Aucun PDF n\'a pu etre fusionne');
            }

            $this->updateProgress([
                'status' => 'processing',
                'message' => 'Generation du fichier final...',
                'percentage' => 50,
                'current' => count($pdfFiles),
                'total' => $bulletins->count()
            ]);

            // Generer le nom du fichier
            $filename = "bulletins_{$this->periodType}_{$this->periodIdentifier}_classe_{$this->classSeriesId}_" . now()->format('Y-m-d_His') . ".pdf";
            $relativePath = "merged_bulletins/{$filename}";
            $fullPath = storage_path('app/public/' . $relativePath);

            $directory = dirname($fullPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Fusionner avec pdfunite
            $escapedFiles = array_map('escapeshellarg', $pdfFiles);
            $escapedOutput = escapeshellarg($fullPath);
            $command = 'pdfunite ' . implode(' ', $escapedFiles) . ' ' . $escapedOutput . ' 2>&1';
            $output = shell_exec($command);

            if (!file_exists($fullPath)) {
                Log::error("pdfunite echoue: " . ($output ?? 'pas de sortie'));
                throw new \Exception('Echec de la fusion PDF: ' . ($output ?? 'pdfunite non disponible'));
            }

            Log::info("PDF fusionne cree: {$filename}");

            $mergedPdf = MergedBulletinPDF::create([
                'class_series_id' => $this->classSeriesId,
                'period_type' => $this->periodType,
                'period_identifier' => $this->periodIdentifier,
                'file_path' => $relativePath,
                'filename' => $filename,
                'bulletin_count' => count($pdfFiles),
                'file_size' => filesize($fullPath),
                'status' => 'completed'
            ]);

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            Log::info("Fusion terminee en {$duration}s", [
                'bulletins_fusionnes' => count($pdfFiles),
                'fichier' => $filename
            ]);

            $this->updateProgress([
                'status' => 'completed',
                'message' => count($pdfFiles) . " bulletins fusionnes avec succes !",
                'percentage' => 100,
                'current' => count($pdfFiles),
                'total' => $bulletins->count(),
                'file_id' => $mergedPdf->id,
                'filename' => $filename,
                'download_url' => "/api/bulletins/download-merged/{$mergedPdf->id}",
                'duration' => $duration
            ], 3600);

        } catch (\Exception $e) {
            Log::error("Erreur fatale lors de la fusion PDF: " . $e->getMessage());

            $this->updateProgress([
                'status' => 'failed',
                'message' => "Erreur: " . $e->getMessage(),
                'percentage' => 0,
                'error' => $e->getMessage()
            ], 3600);

            throw $e;
        }
    }

    protected function updateProgress(array $data, int $ttl = 600)
    {
        \Cache::put($this->progressKey, $data, $ttl);
    }

    public function getProgressKey()
    {
        return $this->progressKey;
    }
}
