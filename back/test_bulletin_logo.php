<?php
// Test script to verify the logo is properly displayed in the bulletin

require_once 'vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\BulletinService;
use App\Http\Controllers\BulletinController;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';

// Create a test request to preview a bulletin
$request = Request::create('/api/bulletins/preview', 'POST', [
    'student_id' => 3, // First student ID we found
    'type' => 'sequence',
    'period_identifier' => 'seq1'
]);

// Process the request through the BulletinController
$response = $app->handle($request);

// Output the response
echo $response->getContent();