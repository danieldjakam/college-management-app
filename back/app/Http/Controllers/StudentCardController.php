<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentCard;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Jobs\GenerateStudentCards;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class StudentCardController extends Controller
{
    /**
     * Générer les cartes d'identité pour une classe entière
     * Format: 10 cartes par page (2 colonnes x 5 lignes)
     */
    public function generateClassCards(Request $request, $classId)
    {
        try {
            $request->validate([
                'academic_year' => 'required|string',
            ]);

            // Récupérer la classe et ses élèves
            $class = SchoolClass::with(['students' => function($query) {
                $query->where('students.is_active', true);
            }])->findOrFail($classId);

            if ($class->students->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun élève trouvé dans cette classe.'
                ], 404);
            }

            $academicYear = $request->academic_year;
            $cardsData = [];

            // Générer les données pour chaque élève
            foreach ($class->students as $student) {
                $cardsData[] = $this->prepareCardData($student, $academicYear);
            }

            // Récupérer les paramètres de mise en page
            $layoutSettings = \App\Models\CardLayoutSetting::getAllSettings();

            // Générer le PDF avec toutes les cartes (10 par page) - NOUVEAU TEMPLATE
            $pdf = Pdf::loadView('student-cards.batch-v2', [
                'cards' => $cardsData,
                'className' => $class->name,
                'academicYear' => $academicYear,
                'settings' => $layoutSettings,
            ]);

            // Configuration du PDF
            $pdf->setPaper('a4', 'portrait');

            $fileName = 'cartes_identite_' . str_replace(' ', '_', $class->name) . '_' . date('Y-m-d') . '.pdf';

            // Retourner le PDF en stream pour le téléchargement AJAX
            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $fileName, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur génération cartes classe: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération des cartes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer une carte individuelle pour un élève
     */
    public function generateSingleCard(Request $request, $studentId)
    {
        try {
            $request->validate([
                'academic_year' => 'required|string',
            ]);

            $student = Student::with(['schoolClass', 'classSeries'])->findOrFail($studentId);
            $academicYear = $request->academic_year;

            $cardData = $this->prepareCardData($student, $academicYear);

            // Récupérer les paramètres de mise en page
            $layoutSettings = \App\Models\CardLayoutSetting::getAllSettings();

            // Générer le PDF pour une seule carte avec le nouveau template
            $pdf = Pdf::loadView('student-cards.single-v2', [
                'card' => $cardData,
                'academicYear' => $academicYear,
                'settings' => $layoutSettings,
            ]);

            // Taille exacte de la carte (85.6mm x 54mm format carte bancaire)
            $pdf->setPaper([0, 0, 242.65, 153.07], 'landscape');

            $fileName = 'carte_' . $student->matricule . '.pdf';

            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $fileName, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur génération carte individuelle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de la carte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Préparer les données d'une carte avec QR Code
     */
    private function prepareCardData($student, $academicYear)
    {
        // Récupérer le nom de la classe
        $className = $student->schoolClass ? $student->schoolClass->name :
                    ($student->classSeries ? $student->classSeries->name : 'N/A');

        // Préparer les données du QR Code
        $qrData = [
            'type' => 'student_id',
            'college' => [
                'name' => 'COLLÈGE POLYVALENT BILINGUE DE DOUALA',
                'logo_url' => url('/images/logo-college.png'),
            ],
            'student' => [
                'matricule' => $student->matricule,
                'nom' => strtoupper($student->last_name),
                'prenom' => ucwords($student->first_name),
                'classe' => $className,
                'annee_scolaire' => $academicYear,
                'photo_url' => $student->photo_url ? url($student->photo_url) : null,
            ],
            'verification_url' => url('/api/student-cards/verify/' . $student->matricule),
            'generated_at' => Carbon::now()->toIso8601String(),
        ];

        // Générer le QR Code en SVG (pas besoin d'imagick)
        $renderer = new ImageRenderer(
            new RendererStyle(200, 1),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString(json_encode($qrData));

        // Convertir SVG en base64
        $qrCode = base64_encode($qrCodeSvg);

        // Récupérer le contact parent/tuteur
        $parentContact = $student->parent_contact ??
                        ($student->parent ? $student->parent->contact : 'N/A');

        // Préparer le chemin de la photo pour DomPDF (chemin absolu local)
        $photoPath = null;
        if ($student->photo) {
            // Utiliser storage_path pour obtenir le chemin absolu local
            $photoPath = storage_path('app/public/' . $student->photo);

            // Vérifier si le fichier existe
            if (!file_exists($photoPath)) {
                \Log::warning("Photo introuvable pour l'élève {$student->id}: {$photoPath}");
                $photoPath = null;
            }
        }

        return [
            'student' => $student,
            'matricule' => $student->matricule,
            'nom' => strtoupper($student->last_name),
            'prenom' => ucwords($student->first_name),
            'classe' => $className,
            'date_naissance' => $student->date_of_birth ?
                               Carbon::parse($student->date_of_birth)->format('d/m/Y') : 'N/A',
            'parent_contact' => $parentContact,
            'photo_url' => $photoPath, // Chemin absolu local pour DomPDF
            'qr_code' => $qrCode,
            'qr_data' => $qrData,
            'card_number' => $student->matricule,
        ];
    }

    /**
     * Prévisualiser la carte d'un élève (HTML)
     */
    public function previewCard(Request $request, $studentId)
    {
        try {
            $request->validate([
                'academic_year' => 'required|string',
            ]);

            $student = Student::with(['schoolClass', 'classSeries'])->findOrFail($studentId);
            $academicYear = $request->academic_year;

            $cardData = $this->prepareCardData($student, $academicYear);

            // Récupérer les paramètres de mise en page
            $layoutSettings = \App\Models\CardLayoutSetting::getAllSettings();

            // Récupérer le token JWT depuis l'en-tête Authorization
            $token = $request->bearerToken();

            // Générer le HTML et le retourner en JSON pour le frontend
            $html = view('student-cards.preview', [
                'card' => $cardData,
                'academicYear' => $academicYear,
                'settings' => $layoutSettings,
                'token' => $token, // Passer le token au template
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur prévisualisation carte: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la prévisualisation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier une carte via QR Code
     */
    public function verifyCard($matricule)
    {
        try {
            $student = Student::where('matricule', $matricule)
                             ->with(['schoolClass', 'classSeries'])
                             ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Élève non trouvé.'
                ], 404);
            }

            $className = $student->schoolClass ? $student->schoolClass->name :
                        ($student->classSeries ? $student->classSeries->name : 'N/A');

            return response()->json([
                'success' => true,
                'college' => [
                    'name' => 'COLLÈGE POLYVALENT BILINGUE DE DOUALA',
                    'logo_url' => url('/images/logo-college.png'),
                ],
                'student' => [
                    'matricule' => $student->matricule,
                    'nom' => strtoupper($student->last_name),
                    'prenom' => ucwords($student->first_name),
                    'classe' => $className,
                    'photo_url' => $student->photo_url ? url($student->photo_url) : null,
                    'is_active' => $student->is_active,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur vérification carte: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification.'
            ], 500);
        }
    }

    /**
     * Lancer la génération en arrière-plan (async)
     */
    public function generateClassCardsAsync(Request $request, $classId)
    {
        try {
            $request->validate([
                'academic_year' => 'required|string',
            ]);

            $class = SchoolClass::findOrFail($classId);
            $studentCount = $class->students()->where('students.is_active', true)->count();

            if ($studentCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun élève actif dans cette classe.'
                ], 404);
            }

            $progressKey = "card_progress_{$classId}_" . time();

            // Initialiser la progression
            Cache::put($progressKey, [
                'current' => 0,
                'total' => $studentCount,
                'percentage' => 0,
                'status' => 'queued',
                'message' => "En file d'attente ({$studentCount} élèves)...",
                'started_at' => now()->toDateTimeString(),
            ], 900);

            // Dispatcher le job
            GenerateStudentCards::dispatch(
                $classId,
                $request->academic_year,
                $progressKey
            )->onQueue('default');

            return response()->json([
                'success' => true,
                'progress_key' => $progressKey,
                'total_students' => $studentCount,
                'message' => 'Génération lancée en arrière-plan',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lancement génération cartes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier la progression de la génération
     */
    public function getProgress($progressKey)
    {
        $progress = Cache::get($progressKey);

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune progression trouvée.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'progress' => $progress,
        ]);
    }

    /**
     * Télécharger le PDF généré
     */
    public function downloadGeneratedCards($progressKey)
    {
        $progress = Cache::get($progressKey);

        if (!$progress || $progress['status'] !== 'completed' || empty($progress['file_path'])) {
            return response()->json([
                'success' => false,
                'message' => 'Le fichier n\'est pas encore prêt.',
            ], 404);
        }

        $fullPath = storage_path('app/public/' . $progress['file_path']);

        if (!file_exists($fullPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier introuvable.',
            ], 404);
        }

        return response()->download($fullPath, $progress['file_name'], [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
