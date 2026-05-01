<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Student;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Common\ErrorCorrectionLevel;

class GenerateStudentCards implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 600;
    public $tries = 1;

    protected $classId;
    protected $academicYear;
    protected $progressKey;

    public function __construct($classId, $academicYear, $progressKey = null)
    {
        $this->classId = $classId;
        $this->academicYear = $academicYear;
        $this->progressKey = $progressKey ?? "card_progress_{$classId}_" . time();
    }

    public function handle(): void
    {
        $startTime = microtime(true);

        $this->updateProgress([
            'current' => 0,
            'total' => 0,
            'percentage' => 0,
            'status' => 'initializing',
            'message' => 'Initialisation...',
            'started_at' => now()->toDateTimeString(),
        ]);

        try {
            $class = SchoolClass::with(['students' => function ($query) {
                $query->where('students.is_active', true)
                      ->orderBy('last_name')
                      ->orderBy('first_name');
            }])->findOrFail($this->classId);

            $students = $class->students;
            $total = $students->count();

            if ($total === 0) {
                $this->updateProgress([
                    'current' => 0, 'total' => 0, 'percentage' => 100,
                    'status' => 'failed',
                    'message' => 'Aucun eleve trouve dans cette classe.',
                ]);
                return;
            }

            $this->updateProgress([
                'current' => 0, 'total' => $total, 'percentage' => 5,
                'status' => 'processing',
                'message' => "Preparation de {$total} cartes...",
                'started_at' => now()->toDateTimeString(),
            ]);

            $cardsData = [];
            $withPhoto = 0;
            $withoutPhoto = 0;

            foreach ($students as $index => $student) {
                $cardData = $this->prepareOptimizedCardData($student);
                $cardsData[] = $cardData;

                if ($cardData['photo_base64']) {
                    $withPhoto++;
                } else {
                    $withoutPhoto++;
                }

                if (($index + 1) % 5 === 0 || $index === $total - 1) {
                    $pct = 5 + round(($index + 1) / $total * 60);
                    $this->updateProgress([
                        'current' => $index + 1, 'total' => $total, 'percentage' => $pct,
                        'status' => 'processing',
                        'message' => "Preparation des donnees: " . ($index + 1) . "/{$total} ({$withPhoto} avec photo, {$withoutPhoto} sans photo)",
                        'with_photo' => $withPhoto, 'without_photo' => $withoutPhoto,
                    ]);
                }
            }

            $this->updateProgress([
                'current' => $total, 'total' => $total, 'percentage' => 70,
                'status' => 'generating_pdf',
                'message' => 'Generation du PDF en cours...',
                'with_photo' => $withPhoto, 'without_photo' => $withoutPhoto,
            ]);

            // Logo
            $logoBase64 = null;
            $logoPath = public_path('assets/logo.png');
            if (file_exists($logoPath)) {
                $logoBase64 = base64_encode(file_get_contents($logoPath));
            }

            $schoolName = 'COLLEGE POLYVALENT BILINGUE DE DOUALA';
            $settings = \App\Models\SchoolSetting::first();
            if ($settings && $settings->school_name) {
                $schoolName = $settings->school_name;
            }

            $pdf = Pdf::loadView('student-cards.batch-v3', [
                'cards' => $cardsData,
                'className' => $class->name,
                'academicYear' => $this->academicYear,
                'logoBase64' => $logoBase64,
                'schoolName' => $schoolName,
            ]);

            $pdf->setPaper('a4', 'portrait');

            $fileName = 'cartes_identite_' . str_replace(' ', '_', $class->name) . '_' . date('Y-m-d_His') . '.pdf';
            $filePath = 'student-cards/' . $fileName;
            $fullPath = storage_path('app/public/' . $filePath);

            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($fullPath, $pdf->output());

            $duration = round(microtime(true) - $startTime, 1);

            $this->updateProgress([
                'current' => $total, 'total' => $total, 'percentage' => 100,
                'status' => 'completed',
                'message' => "{$total} cartes generees en {$duration}s",
                'with_photo' => $withPhoto, 'without_photo' => $withoutPhoto,
                'file_path' => $filePath, 'file_name' => $fileName,
                'duration_seconds' => $duration,
                'completed_at' => now()->toDateTimeString(),
            ]);

            Log::info("Cartes generees avec succes", [
                'class' => $class->name, 'total' => $total,
                'duration' => $duration . 's', 'file' => $filePath,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur generation cartes: ' . $e->getMessage(), [
                'class_id' => $this->classId,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->updateProgress([
                'current' => 0, 'total' => 0, 'percentage' => 0,
                'status' => 'failed',
                'message' => 'Erreur: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ]);
        }

        gc_collect_cycles();
    }

    private function prepareOptimizedCardData(Student $student): array
    {
        $className = $student->classSeries ? $student->classSeries->name :
                    ($student->schoolClass ? $student->schoolClass->name : 'N/A');

        $qrData = json_encode([
            't' => 'sid',
            'm' => $student->matricule,
            'n' => strtoupper($student->last_name) . ' ' . ucwords($student->first_name),
            'c' => $className,
            'y' => $this->academicYear,
            'v' => url('/api/student-cards/verify/' . $student->matricule),
        ]);

        $qrCode = $this->generateQrPngBase64($qrData);

        $photoBase64 = null;
        if ($student->photo) {
            $photoPath = storage_path('app/public/' . $student->photo);
            if (file_exists($photoPath)) {
                $photoBase64 = $this->resizeAndEncodePhoto($photoPath);
            }
        }

        $parentName = $student->parent_name ?? $student->father_name ?? $student->mother_name ?? '';
        $parentPhone = $student->parent_phone ?? $student->mother_phone ?? '';
        $parentContact = trim($parentName . ($parentPhone ? ' - ' . $parentPhone : '')) ?: 'N/A';

        return [
            'student' => $student,
            'matricule' => $student->matricule,
            'nom' => strtoupper($student->last_name),
            'prenom' => ucwords($student->first_name),
            'classe' => $className,
            'date_naissance' => $student->date_of_birth ?
                               Carbon::parse($student->date_of_birth)->format('d/m/Y') : 'N/A',
            'parent_name' => $parentName ?: 'N/A',
            'parent_phone' => $parentPhone ?: 'N/A',
            'parent_contact' => $parentContact,
            'photo_base64' => $photoBase64,
            'qr_code' => $qrCode,
            'card_number' => $student->matricule,
        ];
    }

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

    private function resizeAndEncodePhoto(string $path): ?string
    {
        try {
            $imageInfo = getimagesize($path);
            if (!$imageInfo) return null;

            $mime = $imageInfo['mime'];
            $srcWidth = $imageInfo[0];
            $srcHeight = $imageInfo[1];

            $targetWidth = 150;
            $targetHeight = 150;

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

            if ($mime === 'image/png') {
                imagealphablending($dstImage, false);
                imagesavealpha($dstImage, true);
            }

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
            return 'data:image/jpeg;base64,' . base64_encode(file_get_contents($path));
        }
    }

    protected function updateProgress(array $data, int $ttl = 900): void
    {
        Cache::put($this->progressKey, $data, $ttl);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Job GenerateStudentCards echoue: ' . $e->getMessage());
        $this->updateProgress([
            'status' => 'failed',
            'message' => 'Le job a echoue: ' . $e->getMessage(),
            'error' => $e->getMessage(),
            'percentage' => 0,
        ]);
    }
}
