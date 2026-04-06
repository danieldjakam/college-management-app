<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\CardLayoutSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class GenerateStudentCards implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 600; // 10 minutes max
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
            // Charger la classe avec les élèves
            $class = SchoolClass::with(['students' => function ($query) {
                $query->where('students.is_active', true)
                      ->orderBy('last_name')
                      ->orderBy('first_name');
            }])->findOrFail($this->classId);

            $students = $class->students;
            $total = $students->count();

            if ($total === 0) {
                $this->updateProgress([
                    'current' => 0,
                    'total' => 0,
                    'percentage' => 100,
                    'status' => 'failed',
                    'message' => 'Aucun élève trouvé dans cette classe.',
                ]);
                return;
            }

            $this->updateProgress([
                'current' => 0,
                'total' => $total,
                'percentage' => 5,
                'status' => 'processing',
                'message' => "Préparation de {$total} cartes...",
                'started_at' => now()->toDateTimeString(),
            ]);

            // Pré-charger le template en base64 UNE SEULE FOIS
            $templatePath = public_path('images/carte-template.png');
            $templateBase64 = null;
            if (file_exists($templatePath)) {
                $templateBase64 = base64_encode(file_get_contents($templatePath));
            }

            // Pré-initialiser le QR writer UNE SEULE FOIS
            $renderer = new ImageRenderer(
                new RendererStyle(200, 1),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);

            // Préparer les données de toutes les cartes
            $cardsData = [];
            $withPhoto = 0;
            $withoutPhoto = 0;

            foreach ($students as $index => $student) {
                $cardData = $this->prepareOptimizedCardData($student, $writer, $templateBase64);
                $cardsData[] = $cardData;

                if ($cardData['photo_base64']) {
                    $withPhoto++;
                } else {
                    $withoutPhoto++;
                }

                // Mettre à jour la progression toutes les 5 cartes
                if (($index + 1) % 5 === 0 || $index === $total - 1) {
                    $pct = 5 + round(($index + 1) / $total * 60); // 5% → 65%
                    $this->updateProgress([
                        'current' => $index + 1,
                        'total' => $total,
                        'percentage' => $pct,
                        'status' => 'processing',
                        'message' => "Préparation des données: {$index}/{$total} ({$withPhoto} avec photo, {$withoutPhoto} sans photo)",
                        'with_photo' => $withPhoto,
                        'without_photo' => $withoutPhoto,
                    ]);
                }
            }

            // Générer le PDF
            $this->updateProgress([
                'current' => $total,
                'total' => $total,
                'percentage' => 70,
                'status' => 'generating_pdf',
                'message' => 'Génération du PDF en cours...',
                'with_photo' => $withPhoto,
                'without_photo' => $withoutPhoto,
            ]);

            $layoutSettings = CardLayoutSetting::getAllSettings();

            $pdf = Pdf::loadView('student-cards.batch-optimized', [
                'cards' => $cardsData,
                'className' => $class->name,
                'academicYear' => $this->academicYear,
                'settings' => $layoutSettings,
                'templateBase64' => $templateBase64,
            ]);

            $pdf->setPaper('a4', 'portrait');

            // Sauvegarder le PDF
            $fileName = 'cartes_identite_' . str_replace(' ', '_', $class->name) . '_' . date('Y-m-d_His') . '.pdf';
            $filePath = 'student-cards/' . $fileName;
            $fullPath = storage_path('app/public/' . $filePath);

            // Créer le répertoire si nécessaire
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($fullPath, $pdf->output());

            $duration = round(microtime(true) - $startTime, 1);

            $this->updateProgress([
                'current' => $total,
                'total' => $total,
                'percentage' => 100,
                'status' => 'completed',
                'message' => "{$total} cartes générées en {$duration}s",
                'with_photo' => $withPhoto,
                'without_photo' => $withoutPhoto,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'duration_seconds' => $duration,
                'completed_at' => now()->toDateTimeString(),
            ]);

            Log::info("Cartes générées avec succès", [
                'class' => $class->name,
                'total' => $total,
                'duration' => $duration . 's',
                'file' => $filePath,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur génération cartes: ' . $e->getMessage(), [
                'class_id' => $this->classId,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->updateProgress([
                'current' => 0,
                'total' => 0,
                'percentage' => 0,
                'status' => 'failed',
                'message' => 'Erreur: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ]);
        }

        gc_collect_cycles();
    }

    /**
     * Prépare les données d'une carte de manière optimisée:
     * - Photo redimensionnée et encodée en base64 en mémoire
     * - QR code généré avec writer partagé
     */
    private function prepareOptimizedCardData(Student $student, Writer $writer, ?string $templateBase64): array
    {
        $className = $student->schoolClass ? $student->schoolClass->name :
                    ($student->classSeries ? $student->classSeries->name : 'N/A');

        // QR Code simplifié (moins de données = plus rapide)
        $qrData = json_encode([
            't' => 'sid',
            'm' => $student->matricule,
            'n' => strtoupper($student->last_name) . ' ' . ucwords($student->first_name),
            'c' => $className,
            'y' => $this->academicYear,
            'v' => url('/api/student-cards/verify/' . $student->matricule),
        ]);

        $qrCodeSvg = $writer->writeString($qrData);
        $qrCode = base64_encode($qrCodeSvg);

        // Photo: charger, redimensionner et encoder en base64
        $photoBase64 = null;
        if ($student->photo) {
            $photoPath = storage_path('app/public/' . $student->photo);
            if (file_exists($photoPath)) {
                $photoBase64 = $this->resizeAndEncodePhoto($photoPath);
            }
        }

        $parentContact = $student->parent_contact ??
                        ($student->parent ? $student->parent->contact : 'N/A');

        return [
            'matricule' => $student->matricule,
            'nom' => strtoupper($student->last_name),
            'prenom' => ucwords($student->first_name),
            'classe' => $className,
            'date_naissance' => $student->date_of_birth ?
                               Carbon::parse($student->date_of_birth)->format('d/m/Y') : 'N/A',
            'parent_contact' => $parentContact,
            'photo_base64' => $photoBase64,
            'qr_code' => $qrCode,
            'card_number' => $student->matricule,
        ];
    }

    /**
     * Redimensionne une photo à 200x250px et l'encode en base64
     * Réduit drastiquement la taille pour DomPDF
     */
    private function resizeAndEncodePhoto(string $path): ?string
    {
        try {
            $imageInfo = getimagesize($path);
            if (!$imageInfo) return null;

            $mime = $imageInfo['mime'];
            $srcWidth = $imageInfo[0];
            $srcHeight = $imageInfo[1];

            // Taille cible (suffisant pour 24mm x 24mm à 72dpi)
            $targetWidth = 150;
            $targetHeight = 150;

            // Créer l'image source
            switch ($mime) {
                case 'image/jpeg':
                    $srcImage = imagecreatefromjpeg($path);
                    break;
                case 'image/png':
                    $srcImage = imagecreatefrompng($path);
                    break;
                default:
                    // Fallback: lire le fichier brut
                    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            }

            if (!$srcImage) return null;

            // Créer l'image de destination
            $dstImage = imagecreatetruecolor($targetWidth, $targetHeight);

            // Préserver la transparence pour PNG
            if ($mime === 'image/png') {
                imagealphablending($dstImage, false);
                imagesavealpha($dstImage, true);
            }

            // Redimensionner avec crop centré (pour garder le ratio)
            $srcRatio = $srcWidth / $srcHeight;
            $dstRatio = $targetWidth / $targetHeight;

            if ($srcRatio > $dstRatio) {
                // Image plus large: couper les côtés
                $cropWidth = (int)($srcHeight * $dstRatio);
                $cropX = (int)(($srcWidth - $cropWidth) / 2);
                imagecopyresampled($dstImage, $srcImage, 0, 0, $cropX, 0, $targetWidth, $targetHeight, $cropWidth, $srcHeight);
            } else {
                // Image plus haute: couper le haut/bas
                $cropHeight = (int)($srcWidth / $dstRatio);
                $cropY = (int)(($srcHeight - $cropHeight) / 2);
                imagecopyresampled($dstImage, $srcImage, 0, 0, 0, $cropY, $targetWidth, $targetHeight, $srcWidth, $cropHeight);
            }

            // Encoder en JPEG base64 (qualité 75 = bon compromis taille/qualité)
            ob_start();
            imagejpeg($dstImage, null, 75);
            $imageData = ob_get_clean();

            imagedestroy($srcImage);
            imagedestroy($dstImage);

            return 'data:image/jpeg;base64,' . base64_encode($imageData);

        } catch (\Exception $e) {
            Log::warning("Erreur redimensionnement photo: " . $e->getMessage());
            // Fallback: fichier brut
            return 'data:image/jpeg;base64,' . base64_encode(file_get_contents($path));
        }
    }

    protected function updateProgress(array $data, int $ttl = 900): void
    {
        Cache::put($this->progressKey, $data, $ttl);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Job GenerateStudentCards échoué: ' . $e->getMessage());
        $this->updateProgress([
            'status' => 'failed',
            'message' => 'Le job a échoué: ' . $e->getMessage(),
            'error' => $e->getMessage(),
            'percentage' => 0,
        ]);
    }
}
