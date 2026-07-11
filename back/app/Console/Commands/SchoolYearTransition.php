<?php

namespace App\Console\Commands;

use App\Models\SchoolYear;
use App\Services\SchoolYearTransitionService;
use Illuminate\Console\Command;

class SchoolYearTransition extends Command
{
    protected $signature = 'school-year:transition
        {--source= : ID de l\'annee source (defaut: annee courante)}
        {--name= : Nom de la nouvelle annee (ex: 2026-2027)}
        {--start= : Date de debut (ex: 2026-09-01)}
        {--end= : Date de fin (ex: 2027-06-30)}
        {--no-teachers : Ne pas copier les affectations enseignants}
        {--no-main-teachers : Ne pas copier les professeurs titulaires}
        {--promote-students : Inscrire les eleves dans la nouvelle annee}
        {--force : Executer sans confirmation}';

    protected $description = 'Transition vers une nouvelle annee scolaire (creation des classes, trimestres, sequences, copie du programme)';

    public function handle()
    {
        // Determine source year
        $sourceId = $this->option('source');
        if ($sourceId) {
            $sourceYear = SchoolYear::find($sourceId);
        } else {
            $sourceYear = SchoolYear::where('is_current', true)->first();
        }

        if (!$sourceYear) {
            $this->error('Aucune annee scolaire source trouvee. Utilisez --source=ID');
            return 1;
        }

        $this->info("Annee source: {$sourceYear->name} (ID: {$sourceYear->id})");

        // Get new year details
        $name = $this->option('name') ?: $this->ask('Nom de la nouvelle annee scolaire (ex: 2026-2027)');
        $startDate = $this->option('start') ?: $this->ask('Date de debut (YYYY-MM-DD)', $this->suggestStartDate($sourceYear));
        $endDate = $this->option('end') ?: $this->ask('Date de fin (YYYY-MM-DD)', $this->suggestEndDate($startDate));

        // Check if year already exists
        if (SchoolYear::where('name', $name)->exists()) {
            $this->error("L'annee scolaire '{$name}' existe deja.");
            return 1;
        }

        $service = new SchoolYearTransitionService();

        // Preview
        $options = [
            'copy_teacher_assignments' => !$this->option('no-teachers'),
            'copy_main_teachers' => !$this->option('no-main-teachers'),
            'promote_students' => $this->option('promote-students'),
        ];

        $preview = $service->preview($sourceYear, $options);

        $this->newLine();
        $this->info('=== PREVIEW DE LA TRANSITION ===');
        $this->table(
            ['Element', 'Quantite'],
            [
                ['Classes a dupliquer', $preview['classes_count']],
                ['Eleves concernes', $preview['students_count']],
                ['Matieres/coefficients a copier', $preview['subjects_assignments_count']],
                ['Affectations enseignants', $options['copy_teacher_assignments'] ? $preview['teacher_assignments_count'] : 'Non copie'],
                ['Professeurs titulaires', $options['copy_main_teachers'] ? $preview['main_teachers_count'] : 'Non copie'],
                ['Promotion eleves', $options['promote_students'] ? 'Oui' : 'Non'],
            ]
        );

        $this->newLine();
        $this->info('La nouvelle annee contiendra:');
        $this->line('  - 3 trimestres');
        $this->line('  - 9 sequences (6 regulieres + 3 compositions)');

        if (!$this->option('force') && !$this->confirm('Voulez-vous continuer?')) {
            $this->info('Transition annulee.');
            return 0;
        }

        // Execute
        $this->info('Execution de la transition...');

        try {
            $result = $service->execute($sourceYear, [
                'name' => $name,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ], $options);

            $this->newLine();
            $this->info('=== TRANSITION TERMINEE ===');
            $this->table(
                ['Element', 'Resultat'],
                [
                    ['Nouvelle annee', $result['new_year']->name . ' (ID: ' . $result['new_year']->id . ')'],
                    ['Trimestres crees', $result['trimesters_created']],
                    ['Sequences creees', $result['sequences_created']],
                    ['Classes creees', $result['classes_created']],
                    ['Matieres copiees', $result['subjects_copied']],
                    ['Affectations enseignants', $result['teacher_assignments_copied']],
                    ['Professeurs titulaires', $result['main_teachers_copied']],
                    ['Eleves inscrits', $result['students_promoted']],
                ]
            );

            if ($this->confirm('Voulez-vous definir cette nouvelle annee comme annee courante?')) {
                $service->activateYear($result['new_year']);
                $this->info("L'annee {$result['new_year']->name} est maintenant l'annee courante.");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Erreur: ' . $e->getMessage());
            return 1;
        }
    }

    private function suggestStartDate(SchoolYear $source): string
    {
        return $source->start_date->addYear()->format('Y-m-d');
    }

    private function suggestEndDate(string $startDate): string
    {
        return \Carbon\Carbon::parse($startDate)->addMonths(10)->format('Y-m-d');
    }
}
