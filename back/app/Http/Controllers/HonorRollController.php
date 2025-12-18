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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\MergedHonorRollPDF;
use App\Jobs\MergeHonorRollPDFs;

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
        // Augmenter le temps d'exécution pour cette opération coûteuse
        set_time_limit(300); // 5 minutes

        $trimesterId = $request->input('trimester_id');
        $sectionId = $request->input('section_id');
        $levelId = $request->input('level_id');
        $classId = $request->input('class_id');
        $seriesId = $request->input('series_id');

        \Log::info('Honor roll request received', [
            'trimester_id' => $trimesterId,
            'section_id' => $sectionId,
            'level_id' => $levelId,
            'class_id' => $classId,
            'series_id' => $seriesId,
        ]);

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

        // Important: Toujours avoir au moins un filtre pour éviter de traiter tous les élèves
        if (!$seriesId && !$classId && !$levelId && !$sectionId) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez sélectionner au moins un filtre (section, niveau, classe ou série)'
            ], 400);
        }

        if ($seriesId) {
            // Si c'est un tableau de series_id (sélection multiple)
            if (is_array($seriesId)) {
                $query->whereIn('class_series_id', $seriesId);
            } else {
                $query->where('class_series_id', $seriesId);
            }
        } elseif ($classId) {
            $query->whereHas('classSeries', function ($q) use ($classId) {
                $q->where('class_id', $classId);
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

        \Log::info('Query built, fetching students...');

        $students = $query->with(['classSeries.schoolClass.level.section'])->get();

        \Log::info('Students fetched', ['count' => $students->count()]);

        // Limiter le traitement si trop d'élèves
        if ($students->count() > 500) {
            \Log::warning('Too many students', ['count' => $students->count()]);
            return response()->json([
                'success' => false,
                'message' => "Trop d'élèves à traiter ({$students->count()}). Veuillez affiner vos filtres (maximum 500 élèves)."
            ], 400);
        }

        \Log::info('Processing honor roll for ' . $students->count() . ' students');

        // Calculer les moyennes et filtrer >= 12/20
        $eligibleStudents = [];
        $processedCount = 0;

        foreach ($students as $student) {
            $processedCount++;

            // Log progress every 50 students
            if ($processedCount % 50 === 0) {
                \Log::info("Processed $processedCount / {$students->count()} students");
            }

            try {
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
            } catch (\Exception $e) {
                \Log::error("Error processing student {$student->id}: " . $e->getMessage());
                continue;
            }
        }

        \Log::info("Found " . count($eligibleStudents) . " eligible students out of {$students->count()}");

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

        // Préparer le logo en base64
        $logoPath = $this->getLogoPath();
        $logoBase64 = '';
        if ($logoPath && file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            $mimeType = mime_content_type($logoPath);
            $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        }

        // Récupérer l'année scolaire active
        $currentSchoolYear = \App\Models\SchoolYear::where('is_active', true)->first();
        if ($currentSchoolYear && $currentSchoolYear->start_date && $currentSchoolYear->end_date) {
            $startYear = \Carbon\Carbon::parse($currentSchoolYear->start_date)->year;
            $endYear = \Carbon\Carbon::parse($currentSchoolYear->end_date)->year;
            $academicYear = $startYear . '/' . $endYear;
        } else {
            // Fallback: année courante
            $academicYear = date('Y') . '/' . (date('Y') + 1);
        }

        // Préparer les données pour le template
        $data = [
            'student' => $student,
            'trimester' => $trimester,
            'average' => round($bulletinData['average'], 2),
            'rank' => $bulletinData['rank'],
            'mention' => $mention,
            'total_points' => $bulletinData['totalPoints'] ?? 0,
            'class_name' => $student->classSeries->name ?? 'N/A',
            'academic_year' => $academicYear,
            'generation_date' => now()->format('d/m/Y'),
            'logo_base64' => $logoBase64,
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

    /**
     * Générer en masse tous les certificats pour les élèves éligibles
     */
    public function batchGenerateCertificates(Request $request)
    {
        set_time_limit(600); // 10 minutes

        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'trimester_id' => 'required|exists:trimesters,id',
        ]);

        $studentIds = $request->input('student_ids');
        $trimesterId = $request->input('trimester_id');
        $trimester = Trimester::find($trimesterId);

        $generated = [];
        $failed = [];

        foreach ($studentIds as $studentId) {
            try {
                $student = Student::with(['classSeries.schoolClass.level.section'])->find($studentId);

                if (!$student) {
                    $failed[] = ['id' => $studentId, 'reason' => 'Étudiant introuvable'];
                    continue;
                }

                // Calculer les données du bulletin
                $bulletinData = $this->bulletinService->generateTrimesterBulletinData(
                    $trimester->number,
                    $student->id
                );

                if (!$bulletinData || $bulletinData['average'] < 12.00) {
                    $failed[] = ['id' => $studentId, 'reason' => 'Non éligible (moyenne < 12/20)'];
                    continue;
                }

                $mention = $this->getMention($bulletinData['average']);

                // Préparer le logo en base64
                $logoPath = $this->getLogoPath();
                $logoBase64 = '';
                if ($logoPath && file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $mimeType = mime_content_type($logoPath);
                    $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                }

                // Récupérer l'année scolaire active
                $currentSchoolYear = \App\Models\SchoolYear::where('is_active', true)->first();
                if ($currentSchoolYear && $currentSchoolYear->start_date && $currentSchoolYear->end_date) {
                    $startYear = \Carbon\Carbon::parse($currentSchoolYear->start_date)->year;
                    $endYear = \Carbon\Carbon::parse($currentSchoolYear->end_date)->year;
                    $academicYear = $startYear . '/' . $endYear;
                } else {
                    // Fallback: année courante
                    $academicYear = date('Y') . '/' . (date('Y') + 1);
                }

                // Préparer les données pour le template
                $data = [
                    'student' => $student,
                    'trimester' => $trimester,
                    'average' => round($bulletinData['average'], 2),
                    'rank' => $bulletinData['rank'],
                    'mention' => $mention,
                    'total_points' => $bulletinData['totalPoints'] ?? 0,
                    'class_name' => $student->classSeries->name ?? 'N/A',
                    'academic_year' => $academicYear,
                    'generation_date' => now()->format('d/m/Y'),
                    'logo_base64' => $logoBase64,
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

                // Ajouter le watermark
                $canvas = $dompdf->getCanvas();
                if ($logoPath && file_exists($logoPath)) {
                    $pageWidth = $canvas->get_width();
                    $pageHeight = $canvas->get_height();
                    $logoWidth = 400;
                    $logoHeight = 400;
                    $x = ($pageWidth - $logoWidth) / 2;
                    $y = ($pageHeight - $logoHeight) / 2;

                    $canvas->page_script(function ($pageNumber) use ($canvas, $logoPath, $x, $y, $logoWidth, $logoHeight) {
                        $canvas->set_opacity(0.08);
                        $canvas->image($logoPath, $x, $y, $logoWidth, $logoHeight);
                        $canvas->set_opacity(1.0);
                    });
                }

                $pdfContent = $dompdf->output();

                // Sauvegarder le PDF
                $filename = 'honor_roll_' . $student->id . '_trim' . $trimester->number . '_' . date('Y-m-d') . '.pdf';
                $filePath = 'public/honor_rolls/' . $filename;
                $fullPath = storage_path('app/' . $filePath);

                $directory = dirname($fullPath);
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                file_put_contents($fullPath, $pdfContent);

                $generated[] = [
                    'student_id' => $studentId,
                    'file_path' => $filePath,
                    'filename' => $filename,
                ];

            } catch (\Exception $e) {
                \Log::error("Error generating certificate for student {$studentId}: " . $e->getMessage());
                $failed[] = ['id' => $studentId, 'reason' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($generated) . ' certificats générés avec succès',
            'generated' => $generated,
            'failed' => $failed,
            'total' => count($studentIds),
            'generated_count' => count($generated),
            'failed_count' => count($failed),
        ]);
    }

    /**
     * Fusionner les certificats d'honneur en un seul PDF
     */
    public function mergeCertificates(Request $request)
    {
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '512M');

        $validated = $request->validate([
            'trimester_id' => 'required|exists:trimesters,id',
            'section_id' => 'nullable|exists:sections,id',
            'level_id' => 'nullable|exists:levels,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'series_id' => 'nullable|exists:class_series,id',
        ]);

        // Récupérer tous les fichiers de certificats selon les filtres
        $certificatePaths = [];
        $students = Student::where('is_active', true);

        if ($validated['series_id']) {
            $students->where('class_series_id', $validated['series_id']);
        } elseif ($validated['class_id']) {
            $students->whereHas('classSeries', function ($q) use ($validated) {
                $q->where('class_id', $validated['class_id']);
            });
        } elseif ($validated['level_id']) {
            $students->whereHas('classSeries.schoolClass', function ($q) use ($validated) {
                $q->where('level_id', $validated['level_id']);
            });
        } elseif ($validated['section_id']) {
            $students->whereHas('classSeries.schoolClass.level', function ($q) use ($validated) {
                $q->where('section_id', $validated['section_id']);
            });
        }

        $students = $students->get();

        // Chercher les certificats existants pour ces étudiants
        foreach ($students as $student) {
            $filename = 'honor_roll_' . $student->id . '_trim' . $validated['trimester_id'] . '_' . date('Y-m-d') . '.pdf';
            $filePath = 'public/honor_rolls/' . $filename;
            $fullPath = storage_path('app/' . $filePath);

            if (file_exists($fullPath)) {
                $certificatePaths[] = $filePath;
            }
        }

        if (empty($certificatePaths)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun certificat trouvé pour les critères sélectionnés'
            ], 404);
        }

        // Générer un identifiant unique pour suivre la progression
        $jobId = uniqid('merge_honor_', true);

        // Dispatcher le job de fusion
        MergeHonorRollPDFs::dispatch(
            $validated['trimester_id'],
            $validated['section_id'] ?? null,
            $validated['level_id'] ?? null,
            $validated['class_id'] ?? null,
            $validated['series_id'] ?? null,
            $certificatePaths,
            $jobId
        );

        // Si QUEUE_CONNECTION=sync, le job est déjà terminé
        if (config('queue.default') === 'sync') {
            sleep(1);
            $progress = Cache::get("merge_honor_progress_{$jobId}");

            if ($progress && $progress['status'] === 'completed') {
                return response()->json([
                    'success' => true,
                    'message' => $progress['message'],
                    'job_id' => $jobId,
                    'certificate_count' => count($certificatePaths),
                    'completed' => true,
                    'file_id' => $progress['file_id'] ?? null,
                    'filename' => $progress['filename'] ?? null,
                    'download_url' => $progress['download_url'] ?? null
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Fusion de " . count($certificatePaths) . " certificats en cours...",
            'job_id' => $jobId,
            'certificate_count' => count($certificatePaths),
        ]);
    }

    /**
     * Vérifier la progression de la fusion
     */
    public function getMergeProgress($jobId)
    {
        $progress = Cache::get("merge_honor_progress_{$jobId}", [
            'status' => 'pending',
            'current' => 0,
            'total' => 0,
            'message' => 'En attente...'
        ]);

        // Si mode sync, vérifier directement en DB si fusion récente existe
        if (config('queue.default') === 'sync' && $progress['status'] !== 'completed') {
            $recentMerge = MergedHonorRollPDF::where('status', 'completed')
                ->where('created_at', '>=', now()->subSeconds(60))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($recentMerge) {
                $progress = [
                    'status' => 'completed',
                    'message' => "✅ {$recentMerge->certificate_count} certificats fusionnés avec succès !",
                    'percentage' => 100,
                    'current' => $recentMerge->certificate_count,
                    'total' => $recentMerge->certificate_count,
                    'file_id' => $recentMerge->id,
                    'filename' => $recentMerge->filename,
                    'download_url' => "/api/honor-rolls/merged/{$recentMerge->id}/download"
                ];
            }
        }

        return response()->json($progress);
    }

    /**
     * Télécharger un PDF fusionné
     */
    public function downloadMergedCertificate($mergedId)
    {
        $merged = MergedHonorRollPDF::findOrFail($mergedId);

        if (!Storage::disk('public')->exists($merged->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier PDF introuvable'
            ], 404);
        }

        return Storage::disk('public')->download(
            $merged->file_path,
            $merged->filename,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $merged->filename . '"'
            ]
        );
    }

    /**
     * Lister tous les PDFs fusionnés
     */
    public function listMergedCertificates(Request $request)
    {
        $query = MergedHonorRollPDF::with(['trimester', 'section', 'level', 'schoolClass', 'classSeries'])
            ->orderBy('created_at', 'desc');

        // Filtrage optionnel
        if ($request->has('trimester_id')) {
            $query->where('trimester_id', $request->trimester_id);
        }

        if ($request->has('series_id')) {
            $query->where('series_id', $request->series_id);
        }

        $mergedPdfs = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $mergedPdfs
        ]);
    }
}
