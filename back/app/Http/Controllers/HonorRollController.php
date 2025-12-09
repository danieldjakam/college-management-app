<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Trimester;
use App\Models\Section;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\ClassSeries;
use App\Services\BulletinService;
use Illuminate\Http\Request;
use Dompdf\Dompdf;
use Dompdf\Options;

class HonorRollController extends Controller
{
    protected $bulletinService;

    public function __construct(BulletinService $bulletinService)
    {
        $this->bulletinService = $bulletinService;
    }

    /**
     * Récupérer les élèves éligibles au tableau d'honneur
     * Critères: Moyenne >= 12/20 sur un trimestre
     */
    public function getEligibleStudents(Request $request)
    {
        $trimesterId = $request->input('trimester_id');
        $sectionId = $request->input('section_id');
        $levelId = $request->input('level_id');
        $classId = $request->input('class_id');
        $seriesId = $request->input('series_id');

        if (!$trimesterId) {
            return response()->json([
                'success' => false,
                'message' => 'Le trimestre est requis'
            ], 400);
        }

        // Récupérer le trimestre
        $trimester = Trimester::find($trimesterId);
        if (!$trimester) {
            return response()->json([
                'success' => false,
                'message' => 'Trimestre introuvable'
            ], 404);
        }

        // Construire la requête des étudiants avec filtres
        $query = Student::where('is_active', true);

        if ($seriesId) {
            $query->where('class_series_id', $seriesId);
        } elseif ($classId) {
            $query->whereHas('classSeries', function ($q) use ($classId) {
                $q->where('school_class_id', $classId);
            });
        } elseif ($levelId) {
            $query->whereHas('classSeries.schoolClass', function ($q) use ($levelId) {
                $q->where('level_id', $levelId);
            });
        } elseif ($sectionId) {
            $query->whereHas('classSeries.schoolClass.level', function ($q) use ($sectionId) {
                $q->where('section_id', $sectionId);
            });
        }

        $students = $query->with(['classSeries.schoolClass.level.section'])->get();

        // Calculer les moyennes et filtrer >= 12/20
        $eligibleStudents = [];

        foreach ($students as $student) {
            $bulletinData = $this->bulletinService->generateTrimesterBulletinData(
                $trimester->number,
                $student->id
            );

            if ($bulletinData && $bulletinData['average'] >= 12.00) {
                $mention = $this->getMention($bulletinData['average']);

                $eligibleStudents[] = [
                    'id' => $student->id,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'full_name' => $student->first_name . ' ' . $student->last_name,
                    'date_of_birth' => $student->date_of_birth,
                    'class' => $student->classSeries->name ?? 'N/A',
                    'class_series_id' => $student->class_series_id,
                    'section' => $student->classSeries->schoolClass->level->section->name ?? 'N/A',
                    'level' => $student->classSeries->schoolClass->level->name ?? 'N/A',
                    'average' => round($bulletinData['average'], 2),
                    'rank' => $bulletinData['rank'],
                    'mention' => $mention,
                    'total_points' => $bulletinData['totalPoints'] ?? 0,
                ];
            }
        }

        // Trier par moyenne décroissante
        usort($eligibleStudents, function ($a, $b) {
            return $b['average'] <=> $a['average'];
        });

        // Grouper par mention
        $groupedByMention = [
            'Excellent' => [],
            'Très bien' => [],
            'Bien' => [],
            'Assez bien' => [],
        ];

        foreach ($eligibleStudents as $student) {
            $groupedByMention[$student['mention']][] = $student;
        }

        return response()->json([
            'success' => true,
            'trimester' => [
                'id' => $trimester->id,
                'name' => 'Trimestre ' . $trimester->number,
                'trimester_number' => $trimester->number,
            ],
            'students' => $eligibleStudents,
            'grouped_by_mention' => $groupedByMention,
            'statistics' => [
                'total' => count($eligibleStudents),
                'excellent' => count($groupedByMention['Excellent']),
                'tres_bien' => count($groupedByMention['Très bien']),
                'bien' => count($groupedByMention['Bien']),
                'assez_bien' => count($groupedByMention['Assez bien']),
            ]
        ]);
    }

    /**
     * Déterminer la mention selon la moyenne
     */
    private function getMention($average)
    {
        if ($average >= 18.00) {
            return 'Excellent';
        } elseif ($average >= 16.00) {
            return 'Très bien';
        } elseif ($average >= 14.00) {
            return 'Bien';
        } elseif ($average >= 12.00) {
            return 'Assez bien';
        } else {
            return 'Passable';
        }
    }

    /**
     * Générer le certificat de tableau d'honneur en PDF pour un élève
     */
    public function generateCertificate(Request $request)
    {
        $studentId = $request->input('student_id');
        $trimesterId = $request->input('trimester_id');

        if (!$studentId || !$trimesterId) {
            return response()->json([
                'success' => false,
                'message' => 'ID étudiant et trimestre requis'
            ], 400);
        }

        $student = Student::with(['classSeries.schoolClass.level.section'])->find($studentId);
        $trimester = Trimester::find($trimesterId);

        if (!$student || !$trimester) {
            return response()->json([
                'success' => false,
                'message' => 'Étudiant ou trimestre introuvable'
            ], 404);
        }

        // Calculer les données du bulletin
        $bulletinData = $this->bulletinService->generateTrimesterBulletinData(
            $trimester->number,
            $student->id
        );

        if (!$bulletinData || $bulletinData['average'] < 12.00) {
            return response()->json([
                'success' => false,
                'message' => 'Cet élève n\'est pas éligible au tableau d\'honneur (moyenne < 12/20)'
            ], 400);
        }

        $mention = $this->getMention($bulletinData['average']);

        // Préparer les données pour le template
        $data = [
            'student' => $student,
            'trimester' => $trimester,
            'average' => round($bulletinData['average'], 2),
            'rank' => $bulletinData['rank'],
            'mention' => $mention,
            'total_points' => $bulletinData['totalPoints'] ?? 0,
            'class_name' => $student->classSeries->name ?? 'N/A',
            'academic_year' => '2024/2025', // TODO: Récupérer depuis les settings
            'generation_date' => now()->format('d/m/Y'),
        ];

        // Générer le PDF
        $html = view('honor_roll.certificate', $data)->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Ajouter le watermark (logo en arrière-plan)
        $canvas = $dompdf->getCanvas();
        $logoPath = $this->getLogoPath();

        if ($logoPath && file_exists($logoPath)) {
            $pageCount = $canvas->get_page_count();
            $pageWidth = $canvas->get_width();
            $pageHeight = $canvas->get_height();
            $logoWidth = 400;
            $logoHeight = 400;
            $x = ($pageWidth - $logoWidth) / 2;
            $y = ($pageHeight - $logoHeight) / 2;

            $canvas->page_script(function ($pageNumber) use ($canvas, $logoPath, $x, $y, $logoWidth, $logoHeight) {
                $canvas->set_opacity(0.08); // Très léger pour ne pas gêner la lecture
                $canvas->image($logoPath, $x, $y, $logoWidth, $logoHeight);
                $canvas->set_opacity(1.0);
            });
        }

        $pdfContent = $dompdf->output();

        // Sauvegarder le PDF
        $filename = 'honor_roll_' . $student->id . '_trim' . $trimester->number . '_' . date('Y-m-d') . '.pdf';
        $filePath = 'public/honor_rolls/' . $filename;
        $fullPath = storage_path('app/' . $filePath);

        // Créer le répertoire si nécessaire
        $directory = dirname($fullPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($fullPath, $pdfContent);

        return response()->json([
            'success' => true,
            'message' => 'Certificat généré avec succès',
            'file_path' => $filePath,
            'download_url' => '/api/honor-rolls/download/' . basename($filePath),
        ]);
    }

    /**
     * Télécharger un certificat
     */
    public function downloadCertificate($filename)
    {
        $filePath = storage_path('app/public/honor_rolls/' . $filename);

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier introuvable'
            ], 404);
        }

        return response()->download($filePath);
    }

    /**
     * Récupérer le chemin du logo
     */
    private function getLogoPath()
    {
        // Même logique que BulletinService
        $publicPath = public_path('assets/logo.png');
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        return null;
    }

    /**
     * Récupérer les filtres disponibles (sections, niveaux, classes, séries)
     */
    public function getFilters()
    {
        $sections = Section::where('is_active', true)->get();
        $levels = Level::where('is_active', true)->with('section')->get();
        $classes = SchoolClass::where('is_active', true)->with('level.section')->get();
        $series = ClassSeries::where('is_active', true)->with('schoolClass.level.section')->get();
        $trimesters = Trimester::orderBy('number')->get();

        return response()->json([
            'success' => true,
            'sections' => $sections,
            'levels' => $levels,
            'classes' => $classes,
            'series' => $series,
            'trimesters' => $trimesters,
        ]);
    }
}
