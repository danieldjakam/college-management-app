<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\BulletinGeneration;
use App\Models\BulletinTemplate;
use App\Services\BulletinService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Job Asynchrone pour Génération de Bulletins en Lot
 *
 * Avantages vs. Synchrone:
 * - Retour immédiat à l'utilisateur (< 200ms au lieu de 30-60s)
 * - Pas de timeout 504
 * - Peut générer 100+ bulletins sans problème
 * - Notification à la fin du traitement
 * - Gestion d'erreurs robuste
 */
class GenerateBulletinsBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives en cas d'échec
     */
    public $tries = 3;

    /**
     * Timeout maximum (10 minutes)
     */
    public $timeout = 600;

    /**
     * Paramètres du job
     */
    protected $classId;
    protected $bulletinType;
    protected $periodIdentifier;
    protected $userId; // Pour notifier l'utilisateur
    protected $force;

    /**
     * Créer une nouvelle instance du job
     */
    public function __construct(
        int $classId,
        string $bulletinType,
        string $periodIdentifier,
        int $userId,
        bool $force = false
    ) {
        $this->classId = $classId;
        $this->bulletinType = $bulletinType;
        $this->periodIdentifier = $periodIdentifier;
        $this->userId = $userId;
        $this->force = $force;
    }

    /**
     * Exécuter le job
     */
    public function handle(BulletinService $bulletinService)
    {
        $startTime = microtime(true);

        Log::info("🚀 Début génération bulletins en lot", [
            'class_id' => $this->classId,
            'bulletin_type' => $this->bulletinType,
            'period' => $this->periodIdentifier,
            'user_id' => $this->userId,
            'force' => $this->force
        ]);

        try {
            // Récupérer le template
            $template = BulletinTemplate::where('is_active', true)->first();
            if (!$template) {
                throw new \Exception('Aucun template de bulletin actif trouvé');
            }

            // Récupérer les étudiants
            $students = Student::where('class_series_id', $this->classId)
                ->where('is_active', true)
                ->with([
                    'schoolClass:id,name',
                    'classSeries:id,name,class_id',
                    'classSeries.subjects:id,class_series_id,subject_id,coefficient',
                    'classSeries.subjects.subject:id,name,code,group'
                ])
                ->orderBy('last_name', 'asc')
                ->orderBy('first_name', 'asc')
                ->get();

            if ($students->isEmpty()) {
                throw new \Exception('Aucun étudiant actif trouvé dans cette classe');
            }

            Log::info("📊 {$students->count()} étudiants à traiter");

            // Vérifier les bulletins déjà générés (si not force)
            if (!$this->force) {
                $studentIds = $students->pluck('id')->toArray();
                $existingBulletins = BulletinGeneration::whereIn('student_id', $studentIds)
                    ->where('period_type', $this->bulletinType)
                    ->where('period_identifier', $this->periodIdentifier)
                    ->pluck('student_id')
                    ->toArray();

                $students = $students->filter(function($student) use ($existingBulletins) {
                    return !in_array($student->id, $existingBulletins);
                });

                Log::info("📋 Après filtrage: {$students->count()} bulletins à générer");

                if ($students->isEmpty()) {
                    throw new \Exception('Tous les bulletins ont déjà été générés. Utilisez force=true pour régénérer.');
                }
            }

            // Traiter par lots de 10
            $batchSize = 10;
            $studentBatches = $students->chunk($batchSize);
            $generated = [];
            $errors = [];
            $batchInsertData = [];

            foreach ($studentBatches as $batchIndex => $studentBatch) {
                Log::info("📦 Traitement lot " . ($batchIndex + 1) . " sur " . $studentBatches->count());

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
                            throw new \Exception('Impossible de générer les données du bulletin');
                        }

                        // Générer le HTML
                        $htmlContent = $bulletinService->renderBulletinTemplate($this->bulletinType, $bulletinData, true);

                        // Générer le PDF
                        $filePath = $bulletinService->generatePDF($htmlContent, $filename);

                        // Préparer pour insertion en lot
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

                        $generated[] = [
                            'student_id' => $student->id,
                            'student_name' => $student->first_name . ' ' . $student->last_name
                        ];

                        // Log progression
                        if (count($generated) % 5 === 0) {
                            Log::info("✅ Progression: " . count($generated) . "/{$students->count()} bulletins générés");
                        }

                    } catch (\Exception $e) {
                        Log::error("❌ Erreur génération bulletin étudiant {$student->id}: " . $e->getMessage());
                        $errors[] = [
                            'student_id' => $student->id,
                            'student_name' => $student->first_name . ' ' . $student->last_name,
                            'error' => $e->getMessage()
                        ];
                    }
                }

                // Insertion en lot dans la base
                if (!empty($batchInsertData)) {
                    BulletinGeneration::insert($batchInsertData);
                    $batchInsertData = [];
                }

                // Nettoyage mémoire
                gc_collect_cycles();
            }

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            Log::info("🎉 Génération terminée", [
                'duration' => $duration . 's',
                'generated' => count($generated),
                'errors' => count($errors)
            ]);

            // Notifier l'utilisateur
            $this->notifyUser([
                'success' => true,
                'generated_count' => count($generated),
                'error_count' => count($errors),
                'duration' => $duration,
                'generated' => $generated,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Erreur critique dans GenerateBulletinsBatchJob: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            $this->notifyUser([
                'success' => false,
                'error' => $e->getMessage()
            ]);

            // Re-throw pour marquer le job comme failed
            throw $e;
        }
    }

    /**
     * Notifier l'utilisateur du résultat
     */
    protected function notifyUser(array $result)
    {
        try {
            $user = \App\Models\User::find($this->userId);

            if (!$user) {
                Log::warning("Utilisateur {$this->userId} introuvable pour notification");
                return;
            }

            // Notification via base de données (visible dans l'interface)
            $user->notify(new \App\Notifications\BulletinBatchCompleted($result));

            // Optionnel: Notification par email
            // Mail::to($user->email)->send(new BulletinBatchCompletedMail($result));

            // Optionnel: Notification WhatsApp
            // if ($user->contact) {
            //     SendWhatsAppNotification::dispatch($user->contact, $this->formatWhatsAppMessage($result));
            // }

        } catch (\Exception $e) {
            Log::error("Erreur envoi notification: " . $e->getMessage());
        }
    }

    /**
     * Formater message WhatsApp
     */
    protected function formatWhatsAppMessage(array $result): string
    {
        if ($result['success']) {
            return "✅ *Génération de bulletins terminée*\n\n" .
                   "📊 {$result['generated_count']} bulletins générés\n" .
                   "⏱️ Temps: {$result['duration']}s\n" .
                   ($result['error_count'] > 0 ? "⚠️ {$result['error_count']} erreurs\n" : "") .
                   "\nConnectez-vous pour télécharger les bulletins.";
        } else {
            return "❌ *Erreur génération bulletins*\n\n" .
                   "Erreur: {$result['error']}\n\n" .
                   "Veuillez réessayer ou contacter le support.";
        }
    }

    /**
     * Gérer l'échec du job
     */
    public function failed(\Throwable $exception)
    {
        Log::error("❌ Job GenerateBulletinsBatchJob failed définitivement", [
            'class_id' => $this->classId,
            'user_id' => $this->userId,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->notifyUser([
            'success' => false,
            'error' => 'Le job a échoué après ' . $this->tries . ' tentatives: ' . $exception->getMessage()
        ]);
    }
}
