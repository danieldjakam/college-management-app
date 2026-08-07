<?php

namespace App\Console\Commands;

use App\Models\PaymentTranche;
use App\Models\Student;
use App\Models\SchoolYear;
use App\Models\SchoolSetting;
use App\Models\SmsLog;
use App\Services\NexahSmsService;
use App\Services\PaymentStatusService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPaymentDeadlineReminders extends Command
{
    protected $signature = 'sms:payment-reminders
                            {--dry-run : Simuler sans envoyer}
                            {--days=1 : Nombre de jours avant la deadline}';

    protected $description = 'Envoyer des SMS de rappel aux parents dont une tranche de paiement arrive a echeance';

    public function handle()
    {
        $settings = SchoolSetting::getSettings();
        $dryRun = $this->option('dry-run');
        $daysBefore = (int) $this->option('days');

        if (!$settings->sms_notifications_enabled && !$dryRun) {
            $this->warn('Les notifications SMS sont desactivees.');
            return 0;
        }

        $schoolYear = SchoolYear::where('is_current', true)->first();
        if (!$schoolYear) {
            $this->error('Aucune annee scolaire courante trouvee.');
            return 1;
        }

        // Trouver les tranches dont la deadline est dans $daysBefore jour(s)
        $targetDate = Carbon::today()->addDays($daysBefore);
        $tranches = PaymentTranche::active()
            ->whereNotNull('deadline')
            ->whereDate('deadline', $targetDate)
            ->ordered()
            ->get();

        if ($tranches->isEmpty()) {
            $this->info("Aucune tranche avec deadline le {$targetDate->format('d/m/Y')}.");
            return 0;
        }

        $this->info("Tranches avec deadline le {$targetDate->format('d/m/Y')}:");
        foreach ($tranches as $tranche) {
            $this->line("  - {$tranche->name} (deadline: {$tranche->deadline->format('d/m/Y')})");
        }

        // Recuperer tous les eleves actifs de l'annee courante
        $students = Student::where('school_year_id', $schoolYear->id)
            ->where('is_active', true)
            ->whereNotNull('class_series_id')
            ->where(function ($q) {
                $q->whereNotNull('parent_phone')
                  ->orWhereNotNull('mother_phone');
            })
            ->with(['classSeries.schoolClass'])
            ->get();

        $this->info("Eleves avec contacts parents: {$students->count()}");

        $paymentService = new PaymentStatusService();
        $smsService = $dryRun ? null : new NexahSmsService();

        $sentCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($students as $student) {
            try {
                $status = $paymentService->getStatusForStudent($student, $schoolYear);

                // Verifier chaque tranche en deadline
                foreach ($tranches as $tranche) {
                    $trancheStatus = collect($status->tranche_status)->first(function ($ts) use ($tranche) {
                        return $ts['tranche_id'] === $tranche->id;
                    });

                    if (!$trancheStatus) continue;

                    // Si la tranche est deja payee, on ne rappelle pas
                    if ($trancheStatus['is_fully_paid']) {
                        $skippedCount++;
                        continue;
                    }

                    $remaining = $trancheStatus['remaining_amount'] ?? 0;
                    if ($remaining <= 0) {
                        $skippedCount++;
                        continue;
                    }

                    // Verifier qu'on n'a pas deja envoye un rappel aujourd'hui pour ce student+tranche
                    $alreadySent = SmsLog::where('student_id', $student->id)
                        ->where('type', 'payment_reminder')
                        ->where('status', 'success')
                        ->whereDate('created_at', Carbon::today())
                        ->where('message', 'like', "%{$tranche->name}%")
                        ->exists();

                    if ($alreadySent) {
                        $skippedCount++;
                        continue;
                    }

                    // Construire le message SMS
                    $studentName = trim($student->first_name . ' ' . $student->last_name);
                    $className = $student->classSeries->schoolClass->name ?? '';
                    $deadlineStr = $tranche->deadline->format('d/m/Y');

                    $message = "CPB DOUALA - Rappel de paiement\n"
                        . "Cher parent de {$studentName} ({$className}),\n"
                        . "La {$tranche->name} de {$remaining} FCFA arrive a echeance le {$deadlineStr}.\n"
                        . "Merci de regulariser la situation.\n"
                        . "Cordialement, La Direction.";

                    // Collecter les numeros du parent
                    $phones = [];
                    if (!empty($student->parent_phone)) {
                        $phones[] = $student->parent_phone;
                    }
                    if (!empty($student->mother_phone)) {
                        $phones[] = $student->mother_phone;
                    }

                    if (empty($phones)) continue;

                    if ($dryRun) {
                        $this->line("[DRY-RUN] {$studentName} -> " . implode(', ', $phones) . " | Reste: {$remaining} FCFA");
                        $sentCount++;
                    } else {
                        $result = $smsService->sendSms($phones, $message, [
                            'type' => 'payment_reminder',
                            'student_id' => $student->id,
                            'school_year_id' => $schoolYear->id,
                            'sent_by' => null, // Automatique
                        ]);

                        if ($result['success']) {
                            $sentCount++;
                            $this->line("OK: {$studentName} -> " . implode(', ', $phones));
                        } else {
                            $errorCount++;
                            $this->error("ECHEC: {$studentName} - " . ($result['error'] ?? 'Erreur inconnue'));
                        }
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('SMS reminder error', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Erreur pour {$student->first_name} {$student->last_name}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("=== Resultat ===");
        $this->info("SMS envoyes: {$sentCount}");
        $this->info("Ignores (deja payes/deja notifies): {$skippedCount}");
        if ($errorCount > 0) {
            $this->error("Erreurs: {$errorCount}");
        }

        return 0;
    }
}
