<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\ClassSeriesSubject;

echo "🔍 Debug Min-Max Classe\n";
echo str_repeat('=', 60) . "\n\n";

// Get student ID 3 to find their class
$student = Student::find(3);
if (!$student) {
    echo "❌ Student 3 not found\n";
    exit;
}

echo "📚 Student: {$student->first_name} {$student->last_name}\n";
echo "   Class Series ID: {$student->class_series_id}\n\n";

// Get trimester ID 1
$trimesterId = 1;

// Get all students in the same class
$students = Student::where('class_series_id', $student->class_series_id)->pluck('id');
echo "👥 Students in class: " . $students->count() . "\n\n";

// Get all subjects for this class
$subjects = ClassSeriesSubject::where('class_series_id', $student->class_series_id)->get();
echo "📖 Subjects in class: " . $subjects->count() . "\n\n";

// Check first student
$firstStudentId = $students->first();
echo "🧪 Testing first student (ID: {$firstStudentId}):\n";

$bulletinService = app(App\Services\BulletinService::class);

foreach ($subjects as $subject) {
    // Use reflection to call private method
    $reflection = new ReflectionClass($bulletinService);

    $calculateDS = $reflection->getMethod('calculateDSAverage');
    $calculateDS->setAccessible(true);
    $dsNote = $calculateDS->invoke($bulletinService, $trimesterId, $firstStudentId, $subject->id);

    $getComposition = $reflection->getMethod('getCompositionGrade');
    $getComposition->setAccessible(true);
    $compositionNote = $getComposition->invoke($bulletinService, $trimesterId, $firstStudentId, $subject->id);

    echo "   {$subject->subject->name}:\n";
    echo "      DS: " . ($dsNote ?? 'null') . "\n";
    echo "      Composition: " . ($compositionNote ?? 'null') . "\n";

    if ($dsNote !== null || $compositionNote !== null) {
        echo "      ✅ Au moins une note disponible\n";
        break;
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
