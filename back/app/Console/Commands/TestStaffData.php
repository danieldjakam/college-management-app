<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class TestStaffData extends Command
{
    protected $signature = 'test:staff-data';
    protected $description = 'Test staff data with photos';

    public function handle()
    {
        $this->info('Testing staff data...');

        $staff = User::whereIn('role', ['teacher', 'accountant', 'admin', 'surveillant_general'])
            ->where('is_active', true)
            ->take(3)
            ->get();

        foreach ($staff as $user) {
            $this->info("--- User ID: {$user->id} ---");
            $this->info("Name: {$user->name}");
            $this->info("Email: {$user->email}");
            $this->info("Role: {$user->role}");
            $this->info("Photo field: " . ($user->photo ?: 'NULL'));
            
            // Tester la logique de photo_url
            $photoUrl = $user->photo ? (
                str_starts_with($user->photo, 'http') 
                    ? $user->photo 
                    : url('storage/' . $user->photo)
            ) : null;
            $this->info("Computed photo_url: " . ($photoUrl ?: 'NULL'));
            
            if ($user->photo && str_starts_with($user->photo, 'http')) {
                $this->info("Photo is already full URL");
            }
            
            $this->info("QR Code: " . ($user->qr_code ?: 'NULL'));
            $this->info('');
        }

        return 0;
    }
}