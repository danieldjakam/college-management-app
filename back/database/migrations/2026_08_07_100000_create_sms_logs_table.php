<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->text('message');
            $table->string('type', 50)->default('general');
            $table->unsignedBigInteger('student_id')->nullable()->index();
            $table->unsignedBigInteger('school_year_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('nexah_message_id')->nullable();
            $table->string('error_code', 20)->nullable();
            $table->string('error_description')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('type');
        });

        // Ajouter les champs Nexah SMS dans school_settings
        Schema::table('school_settings', function (Blueprint $table) {
            $table->boolean('sms_notifications_enabled')->default(false)->after('whatsapp_token');
            $table->string('nexah_sms_user')->nullable()->after('sms_notifications_enabled');
            $table->string('nexah_sms_password')->nullable()->after('nexah_sms_user');
            $table->string('nexah_sms_sender_id')->nullable()->default('CPB DOUALA')->after('nexah_sms_password');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');

        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn([
                'sms_notifications_enabled',
                'nexah_sms_user',
                'nexah_sms_password',
                'nexah_sms_sender_id',
            ]);
        });
    }
};
