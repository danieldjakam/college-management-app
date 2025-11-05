<?php

namespace App\Console\Commands;

use App\Models\Teacher;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendTeacherAssignmentsSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:send-teacher-summary {phone_number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoyer le récapitulatif des affectations à un enseignant par WhatsApp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phoneNumber = $this->argument('phone_number');

        $this->info("🔍 Recherche de l'enseignant avec le numéro {$phoneNumber}...");

        // Nettoyer le numéro
        $cleanedPhone = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // Chercher l'enseignant
        $teacher = Teacher::where(function($query) use ($cleanedPhone, $phoneNumber) {
            $query->where('phone_number', $phoneNumber)
                  ->orWhere('phone_number', $cleanedPhone)
                  ->orWhere('phone_number', 'LIKE', '%' . substr($cleanedPhone, -9) . '%');
        })->first();

        if (!$teacher) {
            $this->error("❌ Aucun enseignant trouvé avec le numéro {$phoneNumber}");
            return 1;
        }

        $this->info("✅ Enseignant trouvé: {$teacher->first_name} {$teacher->last_name}");
        $this->info("   ID: {$teacher->id}");
        $this->info("   Téléphone: {$teacher->phone_number}");

        // Récupérer les affectations
        $schoolYear = \App\Models\SchoolYear::where('is_current', true)->first();
        if (!$schoolYear) {
            $this->error("❌ Aucune année scolaire active trouvée");
            return 1;
        }

        $assignments = \App\Models\TeacherAssignment::where('teacher_id', $teacher->id)
            ->where('school_year_id', $schoolYear->id)
            ->where('is_active', true)
            ->with([
                'classSeriesSubject.subject',
                'classSeriesSubject.classSeries.schoolClass.level',
                'schoolYear'
            ])
            ->get();

        $this->info("\n📚 Affectations trouvées: {$assignments->count()}");

        if ($assignments->isEmpty()) {
            $this->warn("⚠️ Aucune affectation trouvée pour cet enseignant");
            return 0;
        }

        foreach ($assignments as $index => $assignment) {
            $subject = $assignment->classSeriesSubject->subject->name;
            $class = $assignment->classSeriesSubject->classSeries->name;
            $this->info("   " . ($index + 1) . ". {$subject} - {$class}");
        }

        $this->newLine();

        if (!$this->confirm('Voulez-vous envoyer le récapitulatif par WhatsApp ?', true)) {
            $this->info('Annulé.');
            return 0;
        }

        $this->info("\n📤 Envoi du récapitulatif par WhatsApp...");

        // Envoyer via WhatsApp
        $whatsAppService = app(WhatsAppService::class);
        $result = $whatsAppService->sendTeacherAssignmentsSummary($teacher, $schoolYear->id);

        if ($result) {
            $this->info("\n✅ Récapitulatif envoyé avec succès à {$teacher->first_name} {$teacher->last_name} au {$teacher->phone_number}");
            $this->info("📱 Vérifiez les logs Laravel pour plus de détails.");
            return 0;
        } else {
            $this->error("\n❌ Échec de l'envoi du récapitulatif");
            $this->error("📝 Vérifiez les logs Laravel pour voir l'erreur détaillée.");
            $this->error("⚙️ Vérifiez également la configuration WhatsApp dans les paramètres de l'école.");
            return 1;
        }
    }
}
