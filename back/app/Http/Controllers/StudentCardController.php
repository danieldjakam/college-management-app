<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentCard;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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

        return [
            'student' => $student,
            'matricule' => $student->matricule,
            'nom' => strtoupper($student->last_name),
            'prenom' => ucwords($student->first_name),
            'classe' => $className,
            'date_naissance' => $student->date_of_birth ?
                               Carbon::parse($student->date_of_birth)->format('d/m/Y') : 'N/A',
            'parent_contact' => $parentContact,
            'photo_url' => $student->photo_url,
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
}
