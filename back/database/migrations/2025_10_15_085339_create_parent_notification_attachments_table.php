<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parent_notification_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_notification_id')->constrained('parent_notifications')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->default('application/pdf');
            $table->integer('file_size'); // en bytes
            $table->string('whatsapp_media_id')->nullable(); // ID du média WhatsApp si envoyé
            $table->boolean('whatsapp_sent')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_notification_attachments');
    }
};
