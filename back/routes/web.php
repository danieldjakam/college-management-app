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
