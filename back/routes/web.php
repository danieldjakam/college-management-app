<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

// Route pour servir les photos des élèves
Route::get('/storage/students/photos/{filename}', function ($filename) {
    $path = 'students/photos/' . $filename;
    
    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }
    
    $file = Storage::disk('public')->get($path);
    $type = Storage::disk('public')->mimeType($path);
    
    return response($file, 200)->header('Content-Type', $type);
})->where('filename', '.*');

// Route de fallback pour login (éviter l'erreur "Route [login] not defined")
Route::get('/login', function (\Illuminate\Http\Request $request) {
    if ($request->expectsJson() || $request->is('api/*')) {
        return response()->json([
            'success' => false,
            'message' => 'Non authentifié. Veuillez vous connecter.',
            'error' => 'Unauthorized'
        ], 401);
    }

    return redirect('/'); // Pour les requêtes web normales
})->name('login');

// ============================================
// 🔥 TEMPORARY: Clear OPcache (REMOVE AFTER FIX!)
// ============================================
Route::get('/clear-opcache-now', function () {
    $results = [];

    if (function_exists('opcache_reset')) {
        opcache_reset();
        $results[] = '✅ OPcache cleared successfully!';
    } else {
        $results[] = '❌ OPcache not available';
    }

    if (function_exists('opcache_invalidate')) {
        opcache_invalidate(__FILE__, true);
        $results[] = '✅ Files invalidated';
    }

    if (function_exists('opcache_get_status')) {
        $status = opcache_get_status();
        $results[] = '📊 OPcache status: ' . ($status !== false ? 'ACTIVE' : 'INACTIVE');
        if ($status && isset($status['opcache_statistics'])) {
            $results[] = '🔢 Cached scripts: ' . $status['opcache_statistics']['num_cached_scripts'];
        }
    }

    $results[] = '🔄 Please refresh your application now!';

    return response()->json([
        'success' => true,
        'results' => $results
    ]);
});

// ============================================
// ADMIN ROUTES - Gestion des groupes de matières
// ============================================
Route::middleware(['auth:api', 'role:admin,principal,directeur_etudes'])->group(function () {
    Route::get('/admin/subject-groups', [App\Http\Controllers\Admin\SubjectGroupsViewController::class, 'index'])
        ->name('admin.subject-groups');
});
