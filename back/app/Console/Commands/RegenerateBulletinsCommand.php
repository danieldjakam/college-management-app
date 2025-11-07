<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SchoolClass;
use App\Models\ClassSeries;
use App\Models\Student;
use App\Models\BulletinGeneration;
use App\Models\BulletinTemplate;
use App\Models\Sequence;
use App\Models\Trimester;
use App\Services\BulletinService;

class RegenerateBulletinsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bulletins:regenerate
                            {class_name : Le nom de la classe ou série (ex: 6em, 6ème A, 5ème, etc.)}
                            {--type= : Type de bulletin (sequence ou trimester)}
                            {--period= : Période (seq1, seq3, trim1, trim2, trim3)}
                            {--force : Forcer la régénération même si les bulletins existent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Régénère les bulletins pour une classe donnée';

    protected $bulletinService;

    public function __construct(BulletinService $bulletinService)
    {
        parent::__construct();
        $this->bulletinService = $bulletinService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $className = $this->argument('class_name');
        $type = $this->option('type');
        $period = $this->option('period');
        $force = $this->option('force');

        $this->info("🚀 Démarrage de la régénération des bulletins pour: {$className}");
        $this->newLine();

        // 1. Trouver la classe ou la série
        $schoolClass = SchoolClass::where('name', 'like', "%{$className}%")->first();
        $classSeries = ClassSeries::where('name', 'like', "%{$className}%")->first();

        if (!$schoolClass && !$classSeries) {
            $this->error("❌ Classe ou série '{$className}' introuvable.");
            $this->info("💡 Classes disponibles:");
            SchoolClass::all()->each(function($class) {
                $this->line("   📚 {$class->name}");
                $series = ClassSeries::where('class_id', $class->id)->get();
                foreach ($series as $s) {
                    $studentCount = Student::where('class_series_id', $s->id)->count();
                    $this->line("      → {$s->name} ({$studentCount} étudiants)");
                }
            });
            return Command::FAILURE;
        }

        // 2. Récupérer les étudiants
        if ($classSeries) {
            // Si une série est trouvée, utiliser seulement cette série
            $this->info("✅ Série trouvée: {$classSeries->name} (ID: {$classSeries->id})");
            $students = Student::where('class_series_id', $classSeries->id)
                ->with(['schoolClass', 'classSeries.subjects'])
                ->get();
        } else {
            // Si c'est une classe, récupérer toutes les séries
            $this->info("✅ Classe trouvée: {$schoolClass->name} (ID: {$schoolClass->id})");
            $seriesIds = ClassSeries::where('class_id', $schoolClass->id)->pluck('id');

            $this->info("📋 Séries de cette classe:");
            ClassSeries::where('class_id', $schoolClass->id)->each(function($s) {
                $count = Student::where('class_series_id', $s->id)->count();
                $this->line("   → {$s->name} ({$count} étudiants)");
            });

            $students = Student::whereIn('class_series_id', $seriesIds)
                ->with(['schoolClass', 'classSeries.subjects'])
                ->get();
        }

        if ($students->isEmpty()) {
            $this->error("❌ Aucun étudiant trouvé dans cette classe.");
            return Command::FAILURE;
        }

        $this->info("👥 {$students->count()} étudiant(s) trouvé(s)");
        $this->newLine();

        // 3. Déterminer la période à générer
        if (!$type || !$period) {
            // Si pas spécifié, demander à l'utilisateur
            $this->warn("⚠️  Type et période non spécifiés. Détection automatique...");

            // Trouver la séquence active
            $activeSequence = Sequence::where('is_active', true)->first();

            if (!$activeSequence) {
                $this->error("❌ Aucune période académique active trouvée.");
                $this->info("💡 Spécifiez manuellement: --type=sequence --period=seq1");
                return Command::FAILURE;
            }

            $type = $activeSequence->is_composition ? 'trimester' : 'sequence';
            $period = $type === 'sequence' ? "seq{$activeSequence->number}" : "trim{$activeSequence->trimester_id}";

            $this->info("📅 Période détectée: {$type} - {$period}");
        }

        // 4. Vérifier le template
        $template = BulletinTemplate::where('type', $type)->where('is_active', true)->first();
        if (!$template) {
            $template = BulletinTemplate::firstOrCreate(
                ['type' => $type, 'name' => 'CPBD Template'],
                [
                    'name' => 'CPBD Template',
                    'type' => $type,
                    'template_html' => 'Default CPBD Template',
                    'is_active' => true,
                    'description' => 'Template par défaut CPBD'
                ]
            );
            $this->info("📝 Template créé automatiquement");
        }

        $this->newLine();
        $this->info("🔄 Début de la génération...");
        $this->newLine();

        // 5. Supprimer les bulletins existants si --force
        if ($force) {
            $deleted = BulletinGeneration::whereIn('student_id', $students->pluck('id'))
                ->where('period_type', $type)
                ->where('period_identifier', $period)
                ->count();

            if ($deleted > 0) {
                BulletinGeneration::whereIn('student_id', $students->pluck('id'))
                    ->where('period_type', $type)
                    ->where('period_identifier', $period)
                    ->delete();

                $this->warn("🗑️  {$deleted} bulletin(s) existant(s) supprimé(s)");
            }
        }

        // 6. Générer les bulletins avec barre de progression
        $progressBar = $this->output->createProgressBar($students->count());
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $progressBar->setMessage('Initialisation...');
        $progressBar->start();

        $generated = 0;
        $errors = [];
        $startTime = microtime(true);

        foreach ($students as $student) {
            $progressBar->setMessage("Génération pour {$student->first_name} {$student->last_name}...");

            try {
                // Vérifier si déjà généré
                $existing = BulletinGeneration::where('student_id', $student->id)
                    ->where('period_type', $type)
                    ->where('period_identifier', $period)
                    ->first();

                if ($existing && !$force) {
                    $progressBar->advance();
                    continue;
                }

                // Générer les données du bulletin
                $bulletinData = null;
                $filename = null;

                if ($type === 'sequence') {
                    $sequenceNumber = (int) str_replace('seq', '', $period);
                    $bulletinData = $this->bulletinService->generateSequenceBulletinData($sequenceNumber, $student->id);
                    $filename = "bulletin_sequence_{$sequenceNumber}_{$student->id}_" . now()->format('Y-m-d') . ".pdf";
                } elseif ($type === 'trimester') {
                    $trimesterNumber = (int) str_replace('trim', '', $period);
                    $bulletinData = $this->bulletinService->generateTrimesterBulletinData($trimesterNumber, $student->id);
                    $filename = "bulletin_trimestre_{$trimesterNumber}_{$student->id}_" . now()->format('Y-m-d') . ".pdf";
                }

                if (!$bulletinData) {
                    throw new \Exception('Impossible de générer les données du bulletin');
                }

                // Rendre le template HTML
                $htmlContent = $this->bulletinService->renderBulletinTemplate($type, $bulletinData, true);

                // Générer le PDF
                $filePath = $this->bulletinService->generatePDF($htmlContent, $filename);

                // Créer l'enregistrement
                BulletinGeneration::create([
                    'student_id' => $student->id,
                    'template_id' => $template->id,
                    'period_type' => $type,
                    'period_identifier' => $period,
                    'file_path' => $filePath,
                    'generated_at' => now(),
                    'is_complete' => true,
                    'completion_percentage' => 100.0
                ]);

                $generated++;

            } catch (\Exception $e) {
                $errors[] = [
                    'student' => "{$student->first_name} {$student->last_name}",
                    'error' => $e->getMessage()
                ];
            }

            $progressBar->advance();
        }

        $progressBar->setMessage('Terminé!');
        $progressBar->finish();
        $this->newLine(2);

        // 7. Afficher les résultats
        $duration = round(microtime(true) - $startTime, 2);

        $this->info("✅ Génération terminée en {$duration}s");
        $this->info("📊 Résultats:");
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Étudiants traités', $students->count()],
                ['Bulletins générés', $generated],
                ['Erreurs', count($errors)],
                ['Durée', "{$duration}s"],
            ]
        );

        // 8. Afficher les erreurs si présentes
        if (!empty($errors)) {
            $this->newLine();
            $this->error("❌ Erreurs rencontrées:");
            $this->table(
                ['Étudiant', 'Erreur'],
                array_map(fn($e) => [$e['student'], $e['error']], $errors)
            );
        }

        $this->newLine();
        $this->info("🎉 Script terminé avec succès!");

        return Command::SUCCESS;
    }
}
