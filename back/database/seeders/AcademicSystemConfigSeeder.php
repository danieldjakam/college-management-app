<?php

namespace Database\Seeders;

use App\Models\AcademicSystemConfig;
use Illuminate\Database\Seeder;

class AcademicSystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        AcademicSystemConfig::create([
            'type' => 'trimester',
            'periods_count' => 3,
            'is_active' => true,
            'description' => 'Configuration par défaut - Système par trimestre'
        ]);
    }
}
