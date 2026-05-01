<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\ClassSeries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Jobs\GenerateStudentCards;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Common\ErrorCorrectionLevel;

class StudentCardController extends Controller
{
    /**
     * Generate QR code as PNG base64 using GD (DomPDF compatible)
     */
    private function generateQrPngBase64(string $data): string
    {
        $qrCode = Encoder::encode($data, ErrorCorrectionLevel::L());
        $matrix = $qrCode->getMatrix();
        $moduleSize = 8;
        $matrixWidth = $matrix->getWidth();
        $imgSize = $matrixWidth * $moduleSize;

        $img = imagecreatetruecolor($imgSize, $imgSize);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        for ($y = 0; $y < $matrixWidth; $y++) {
            for ($x = 0; $x < $matrixWidth; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    imagefilledrectangle($img, $x * $moduleSize, $y * $moduleSize, ($x + 1) * $moduleSize - 1, ($y + 1) * $moduleSize - 1, $black);
                }
            }
        }

        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();
        imagedestroy($img);

        return base64_encode($pngData);
    }

    /**
     * Charger le logo de l'ecole en base64
     */
    private function getLogoBase64(): ?string
    {
        $logoPath = public_path('assets/logo.png');
        if (file_exists($logoPath)) {
            return base64_encode(file_get_contents($logoPath));
        }
        return null;
    }

    /**
     * Recuperer le nom de l'ecole
     */
    private function getSchoolName(): string
    {
        $settings = \App\Models\SchoolSetting::first();
        return $settings->school_name ?? 'COLLEGE POLYVALENT BILINGUE DE DOUALA';
    }

    /**
     * Generer les cartes d'identite pour une classe entiere
     * Format: 10 cartes par page (2 colonnes x 5 lignes)
     */
    public function generateClassCards(Request $request, $classId)
    {
        try {
            $request->validate([
                'academic_year' => 'required|string',
            ]);

            // Chercher d'abord dans ClassSeries, sinon SchoolClass
            $class = ClassSeries::with(['students' => function($query) {
                $query->where('students.is_active', true)
                      ->orderBy('last_name')
                      ->orderBy('first_name');
            }])->find($classId);

            if (!$class) {
                $class = SchoolClass::with(['students' => function($query) {
                    $query->where('students.is_active', true)
                          ->orderBy('last_name')
                          ->orderBy('first_name');
                }])->findOrFail($classId);
            }

            if ($class->students->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun eleve trouve dans cette classe.'
                ], 404);
            }

            set_time_limit(300);

            $academicYear = $request->academic_year;
            $cardsData = [];

            foreach ($class->students as $student) {
                $cardsData[] = $this->prepareCardData($student, $academicYear);
            }

            $pdf = Pdf::loadView('student-cards.batch-v3', [
                'cards' => $cardsData,
                'className' => $class->name,
                'academicYear' => $academicYear,
                'logoBase64' => $this->getLogoBase64(),
                'schoolName' => $this->getSchoolName(),
            ]);

            $pdf->setPaper('a4', 'portrait');

            $fileName = 'cartes_identite_' . str_replace(' ', '_', $class->name) . '_' . date('Y-m-d') . '.pdf';

            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $fileName, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur generation cartes classe: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la generation des cartes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generer une carte individuelle pour un eleve
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

            $pdf = Pdf::loadView('student-cards.single-v3', [
                'card' => $cardData,
                'academicYear' => $academicYear,
                'logoBase64' => $this->getLogoBase64(),
                'schoolName' => $this->getSchoolName(),
            ]);

            $pdf->setPaper([0, 0, 242.65, 153.07], 'landscape');

            $fileName = 'carte_' . $student->matricule . '.pdf';

            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $fileName, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur generation carte individuelle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la generation de la carte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preparer les donnees d'une carte avec QR Code
     */
    private function prepareCardData($student, $academicYear)
    {
        $className = $student->classSeries ? $student->classSeries->name :
                    ($student->schoolClass ? $student->schoolClass->name : 'N/A');

        $qrData = json_encode([
            't' => 'sid',
            'm' => $student->matricule,
            'n' => strtoupper($student->last_name) . ' ' . ucwords($student->first_name),
            'c' => $className,
            'v' => url('/api/student-cards/verify/' . $student->matricule),
        ]);

        $qrCode = $this->generateQrPngBase64($qrData);

        $parentName = $student->parent_name ?? $student->father_name ?? $student->mother_name ?? '';
        $parentPhone = $student->parent_phone ?? $student->mother_phone ?? '';
        $parentContact = trim($parentName . ($parentPhone ? ' - ' . $parentPhone : '')) ?: 'N/A';

        $photoData = null;
        if ($student->photo) {
            $photoPath = storage_path('app/public/' . $student->photo);
            if (file_exists($photoPath)) {
                $photoData = $this->resizePhoto($photoPath);
            }
        }

        return [
            'student' => $student,
            'matricule' => $student->matricule,
            'nom' => strtoupper($student->last_name),
            'prenom' => ucwords($student->first_name),
            'classe' => $className,
            'photo_base64' => $photoData,
            'date_naissance' => $student->date_of_birth ?
                               Carbon::parse($student->date_of_birth)->format('d/m/Y') : 'N/A',
            'parent_contact' => $parentContact,
            'qr_code' => $qrCode,
            'card_number' => $student->matricule,
        ];
    }

    /**
     * Previsualiser la carte d'un eleve (HTML)
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

            $html = view('student-cards.single-v3', [
                'card' => $cardData,
                'academicYear' => $academicYear,
                'logoBase64' => $this->getLogoBase64(),
                'schoolName' => $this->getSchoolName(),
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur previsualisation carte: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la previsualisation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifier une carte via QR Code
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
                    'message' => 'Eleve non trouve.'
                ], 404);
            }

            $className = $student->schoolClass ? $student->schoolClass->name :
                        ($student->classSeries ? $student->classSeries->name : 'N/A');

            return response()->json([
                'success' => true,
                'college' => [
                    'name' => 'COLLEGE POLYVALENT BILINGUE DE DOUALA',
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
            Log::error('Erreur verification carte: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la verification.'
            ], 500);
        }
    }

    /**
     * Lancer la generation en arriere-plan (async)
     */
    public function generateClassCardsAsync(Request $request, $classId)
    {
        try {
            $request->validate([
                'academic_year' => 'required|string',
            ]);

            $class = ClassSeries::find($classId) ?? SchoolClass::findOrFail($classId);
            $studentCount = $class->students()->where('students.is_active', true)->count();

            if ($studentCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun eleve actif dans cette classe.'
                ], 404);
            }

            $progressKey = "card_progress_{$classId}_" . time();

            Cache::put($progressKey, [
                'current' => 0,
                'total' => $studentCount,
                'percentage' => 0,
                'status' => 'queued',
                'message' => "En file d'attente ({$studentCount} eleves)...",
                'started_at' => now()->toDateTimeString(),
            ], 900);

            GenerateStudentCards::dispatch(
                $classId,
                $request->academic_year,
                $progressKey
            )->onQueue('default');

            return response()->json([
                'success' => true,
                'progress_key' => $progressKey,
                'total_students' => $studentCount,
                'message' => 'Generation lancee en arriere-plan',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lancement generation cartes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifier la progression de la generation
     */
    public function getProgress($progressKey)
    {
        $progress = Cache::get($progressKey);

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune progression trouvee.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'progress' => $progress,
        ]);
    }

    /**
     * Telecharger le PDF genere
     */
    public function downloadGeneratedCards($progressKey)
    {
        $progress = Cache::get($progressKey);

        if (!$progress || $progress['status'] !== 'completed' || empty($progress['file_path'])) {
            return response()->json([
                'success' => false,
                'message' => 'Le fichier n\'est pas encore pret.',
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

    /**
     * Redimensionner une photo pour DomPDF (150x150px, JPEG qualite 75)
     */
    private function resizePhoto(string $path): ?string
    {
        try {
            $imageInfo = getimagesize($path);
            if (!$imageInfo) return null;

            $mime = $imageInfo['mime'];
            $srcWidth = $imageInfo[0];
            $srcHeight = $imageInfo[1];
            $targetWidth = 150;
            $targetHeight = 180;

            switch ($mime) {
                case 'image/jpeg':
                    $srcImage = imagecreatefromjpeg($path);
                    break;
                case 'image/png':
                    $srcImage = imagecreatefrompng($path);
                    break;
                default:
                    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            }

            if (!$srcImage) return null;

            $dstImage = imagecreatetruecolor($targetWidth, $targetHeight);

            $srcRatio = $srcWidth / $srcHeight;
            $dstRatio = $targetWidth / $targetHeight;

            if ($srcRatio > $dstRatio) {
                $cropWidth = (int)($srcHeight * $dstRatio);
                $cropX = (int)(($srcWidth - $cropWidth) / 2);
                imagecopyresampled($dstImage, $srcImage, 0, 0, $cropX, 0, $targetWidth, $targetHeight, $cropWidth, $srcHeight);
            } else {
                $cropHeight = (int)($srcWidth / $dstRatio);
                $cropY = (int)(($srcHeight - $cropHeight) / 2);
                imagecopyresampled($dstImage, $srcImage, 0, 0, 0, $cropY, $targetWidth, $targetHeight, $srcWidth, $cropHeight);
            }

            ob_start();
            imagejpeg($dstImage, null, 75);
            $imageData = ob_get_clean();

            imagedestroy($srcImage);
            imagedestroy($dstImage);

            return 'data:image/jpeg;base64,' . base64_encode($imageData);
        } catch (\Exception $e) {
            Log::warning("Erreur redimensionnement photo: " . $e->getMessage());
            return null;
        }
    }
}
