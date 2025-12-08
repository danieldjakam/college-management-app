<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Student;
use App\Models\BulletinGeneration;
use App\Models\BulletinTemplate;
use App\Services\BulletinService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateBulletinBatch implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 900; // 15 minutes max
    public $tries = 1; // Pas de retry automatique

    protected $classId;
    protected $bulletinType;
    protected $periodIdentifier;
    protected $force;
    protected $progressKey;

    /**
     * Create a new job instance.
     */
    public function __construct($classId, $bulletinType, $periodIdentifier, $force = false, $progressKey = null)
    {
        $this->classId = $classId;
        $this->bulletinType = $bulletinType;
        $this->periodIdentifier = $periodIdentifier;
        $this->force = $force;
        $this->progressKey = $progressKey ?? "bulletin_progress_{$classId}_{$periodIdentifier}_" . time();
    }

    /**
     * Execute the job.
     */
    public function handle(BulletinService $bulletinService): void
    {
        $startTime = microtime(true);

        Log::info("🚀 Job de génération batch démarré", [
            'class_id' => $this->classId,
            'type' => $this->bulletinType,
            'period' => $this->periodIdentifier,
            'progress_key' => $this->progressKey
        ]);

        // Initialiser la progression
        $this->updateProgress([
            'current' => 0,
            'total' => 0,
            'percentage' => 0,
            'status' => 'initializing',
            'message' => 'Initialisation...',
            'started_at' => now()->toDateTimeString()
        ]);

        try {
            // Récupérer les séries de la classe
            $seriesIds = \App\Models\ClassSeries::where('class_id', $this->classId)
                ->pluck('id');

            // Charger les étudiants avec eager loading
            $students = Student::whereIn('class_series_id', $seriesIds)
                ->where('is_active', true)
                ->with([
                    'schoolClass' => function($query) {
                        $query->select('school_classes.id', 'school_classes.name');
                    },
                    'classSeries:id,name,class_id'
                ])
                ->select(['id', 'name', 'subname', 'class_series_id', 'is_active', 'birthday', 'sex'])
                ->orderBy('name')
                ->limit(500)
                ->get();

            Log::info("📊 {$students->count()} étudiants trouvés");

            // Mettre à jour avec le nombre total
            $this->updateProgress([
                'current' => 0,
                'total' => $students->count(),
                'percentage' => 0,
                'status' => 'loading',
                'message' => "Chargement de {$students->count()} étudiants...",
                'started_at' => now()->toDateTimeString()
            ]);

            // Si force=true, supprimer les bulletins existants
            if ($this->force) {
                $existingBulletins = BulletinGeneration::whereIn('student_id', $students->pluck('id'))
                    ->where('period_type', $this->bulletinType)
                    ->where('period_identifier', $this->periodIdentifier)
                    ->get();

                $deletedCount = 0;
                foreach ($existingBulletins as $bulletin) {
                    if ($bulletin->file_path && file_exists(storage_path('app/' . $bulletin->file_path))) {
                        @unlink(storage_path('app/' . $bulletin->file_path));
                    }
                    $bulletin->delete();
                    $deletedCount++;
                }

                Log::info("🗑️ {$deletedCount} bulletins existants supprimés");
            }

            // Filtrer les étudiants qui ont déjà un bulletin (si pas force)
            if (!$this->force) {
                $existingBulletins = BulletinGeneration::whereIn('student_id', $students->pluck('id'))
                    ->where('period_type', $this->bulletinType)
                    ->where('period_identifier', $this->periodIdentifier)
                    ->pluck('student_id')
                    ->toArray();

                $students = $students->filter(function($student) use ($existingBulletins) {
                    return !in_array($student->id, $existingBulletins);
                });

                Log::info("📋 {$students->count()} étudiants à traiter après filtrage");
            }

            if ($students->isEmpty()) {
                $this->updateProgress([
                    'current' => 0,
                    'total' => 0,
                    'percentage' => 100,
                    'status' => 'completed',
                    'message' => 'Tous les bulletins sont déjà générés',
                    'started_at' => now()->toDateTimeString(),
                    'completed_at' => now()->toDateTimeString()
                ]);
                return;
            }

            // Mettre à jour le statut
            $this->updateProgress([
                'current' => 0,
                'total' => $students->count(),
                'percentage' => 0,
                'status' => 'processing',
                'message' => "Génération de {$students->count()} bulletins...",
                'started_at' => now()->toDateTimeString()
            ]);

            // Récupérer le template
            $template = BulletinTemplate::where('type', $this->bulletinType)->where('is_active', true)->first();
            if (!$template) {
                $template = BulletinTemplate::firstOrCreate(
                    ['type' => $this->bulletinType, 'name' => 'CPBD Template'],
                    [
                        'name' => 'CPBD Template',
                        'type' => $this->bulletinType,
                        'template_html' => 'Default CPBD Template',
                        'is_active' => true,
                        'description' => 'Template par défaut CPBD'
                    ]
                );
            }

            $generated = [];
            $errors = [];
            $batchInsertData = [];
            $batchSize = 25; // Traiter par lots de 25
            $studentBatches = $students->chunk($batchSize);

            foreach ($studentBatches as $batchIndex => $studentBatch) {
                foreach ($studentBatch as $student) {
                    try {
                        // Générer les données du bulletin
                        $bulletinData = null;
                        $filename = null;

                        if ($this->bulletinType === 'sequence') {
                            $sequenceNumber = (int) str_replace('seq', '', $this->periodIdentifier);
                            $bulletinData = $bulletinService->generateSequenceBulletinData($sequenceNumber, $student->id);
                            $filename = "bulletin_sequence_{$sequenceNumber}_{$student->id}_" . now()->format('Y-m-d') . ".pdf";
                        } elseif ($this->bulletinType === 'trimester') {
                            $trimesterNumber = (int) str_replace('trim', '', $this->periodIdentifier);
                            $bulletinData = $bulletinService->generateTrimesterBulletinData($trimesterNumber, $student->id);
                            $filename = "bulletin_trimestre_{$trimesterNumber}_{$student->id}_" . now()->format('Y-m-d') . ".pdf";
                        }

                        if (!$bulletinData) {
                            throw new \Exception('Unable to generate bulletin data');
                        }

                        // Rendre le template HTML
                        $htmlContent = $bulletinService->renderBulletinTemplate($this->bulletinType, $bulletinData, true);

                        // Générer le PDF
                        $filePath = $bulletinService->generatePDF($htmlContent, $filename);

                        // Préparer pour insertion batch
                        $batchInsertData[] = [
                            'student_id' => $student->id,
                            'template_id' => $template->id,
                            'period_type' => $this->bulletinType,
                            'period_identifier' => $this->periodIdentifier,
                            'file_path' => $filePath,
                            'generated_at' => now(),
                            'is_complete' => true,
                            'completion_percentage' => 100.0,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];

                        $generated[] = $student->id;

                        // Mettre à jour la progression
                        $current = count($generated) + count($errors);
                        $percentage = round(($current / $students->count()) * 100, 1);

                        $this->updateProgress([
                            'current' => $current,
                            'total' => $students->count(),
                            'percentage' => $percentage,
                            'generated_count' => count($generated),
                            'error_count' => count($errors),
                            'status' => 'processing',
                            'message' => "{$current}/{$students->count()} bulletins traités ({$percentage}%)",
                            'started_at' => Cache::get($this->progressKey)['started_at'] ?? now()->toDateTimeString()
                        ]);

                    } catch (\Exception $e) {
                        Log::error("❌ Erreur génération bulletin pour élève {$student->id}: " . $e->getMessage());
                        $errors[] = [
                            'student_id' => $student->id,
                            'student_name' => $student->name . ' ' . ($student->subname ?? ''),
                            'error' => $e->getMessage()
                        ];
                    }
                }

                // Insérer le batch dans la DB
                if (!empty($batchInsertData)) {
                    BulletinGeneration::insert($batchInsertData);
                    $batchInsertData = [];
                }

                // Nettoyer la mémoire
                gc_collect_cycles();
            }

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            Log::info("🎉 Génération terminée en {$duration}s", [
                'generated' => count($generated),
                'errors' => count($errors)
            ]);

            // Marquer comme terminé
            $this->updateProgress([
                'current' => $students->count(),
                'total' => $students->count(),
                'percentage' => 100,
                'generated_count' => count($generated),
                'error_count' => count($errors),
                'duration_seconds' => $duration,
                'status' => 'completed',
                'message' => "Terminé ! " . count($generated) . " bulletins générés, " . count($errors) . " erreurs",
                'started_at' => Cache::get($this->progressKey)['started_at'] ?? now()->toDateTimeString(),
                'completed_at' => now()->toDateTimeString()
            ], 3600); // Garder 1 heure

        } catch (\Exception $e) {
            Log::error("❌ Erreur fatale dans le job de génération batch: " . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);

            $this->updateProgress([
                'current' => 0,
                'total' => 0,
                'percentage' => 0,
                'status' => 'failed',
                'message' => "Erreur: " . $e->getMessage(),
                'error' => $e->getMessage(),
                'started_at' => Cache::get($this->progressKey)['started_at'] ?? now()->toDateTimeString(),
                'failed_at' => now()->toDateTimeString()
            ], 3600);

            throw $e; // Re-throw pour que Laravel marque le job comme failed
        }
    }

    /**
     * Mettre à jour la progression dans le cache
     */
    protected function updateProgress(array $data, int $ttl = 900)
    {
        Cache::put($this->progressKey, $data, $ttl);
    }

    /**
     * Récupérer la clé de progression
     */
    public function getProgressKey()
    {
        return $this->progressKey;
    }
}
