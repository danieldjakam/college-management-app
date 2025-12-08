<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_layout_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->string('setting_value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insérer les valeurs par défaut
        DB::table('card_layout_settings')->insert([
            // Photo settings
            ['setting_key' => 'photo_top', 'setting_value' => '11', 'description' => 'Photo top position (mm)', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'photo_right', 'setting_value' => '8', 'description' => 'Photo right position (mm)', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'photo_width', 'setting_value' => '24', 'description' => 'Photo width (mm)', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'photo_height', 'setting_value' => '24', 'description' => 'Photo height (mm)', 'created_at' => now(), 'updated_at' => now()],
            
            // QR Code settings
            ['setting_key' => 'qr_bottom', 'setting_value' => '4', 'description' => 'QR code bottom position (mm)', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'qr_width', 'setting_value' => '14', 'description' => 'QR code width (mm)', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'qr_height', 'setting_value' => '14', 'description' => 'QR code height (mm)', 'created_at' => now(), 'updated_at' => now()],
            
            // Info text settings
            ['setting_key' => 'info_top', 'setting_value' => '20.5', 'description' => 'Info text top position (mm)', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'info_left', 'setting_value' => '33', 'description' => 'Info text left position (mm)', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'info_font_size', 'setting_value' => '4.5', 'description' => 'Info text font size (pt)', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'info_line_height', 'setting_value' => '3.8', 'description' => 'Info text line height (mm)', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'info_max_width', 'setting_value' => '40', 'description' => 'Info text max width (mm)', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('card_layout_settings');
    }
};
