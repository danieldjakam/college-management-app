<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Http\Controllers\StaffAttendanceController;
use Illuminate\Http\Request;

class TestBadgeGeneration extends Command
{
    protected $signature = 'test:badge-generation {user_id}';
    protected $description = 'Test badge PDF generation for a staff member';

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return 1;
        }
        
        $this->info("Testing badge generation for: {$user->name} ({$user->role})");
        
        try {
            $controller = new StaffAttendanceController();
            $request = new Request(['user_id' => $userId]);
            
            // Simuler la validation
            $request->merge(['user_id' => $userId]);
            
            $this->info("Generating badge PDF...");
            
            // Appeler la méthode directement
            $qrCode = 'STAFF_' . $user->id;
            $badgeHtml = $this->callPrivateMethod($controller, 'generateBadgeHtmlForPDF', [$user, $qrCode]);
            
            // Sauvegarder le HTML pour vérification
            $htmlFile = storage_path('app/test_badge.html');
            file_put_contents($htmlFile, $badgeHtml);
            
            $this->info("Badge HTML generated successfully!");
            $this->info("HTML saved to: {$htmlFile}");
            $this->info("You can open this file in a browser to preview the badge");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Error generating badge: " . $e->getMessage());
            return 1;
        }
    }
    
    private function callPrivateMethod($object, $methodName, $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}