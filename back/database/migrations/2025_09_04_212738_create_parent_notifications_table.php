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
        Schema::create('parent_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parent_guardians')->onDelete('cascade');
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('cascade');
            $table->string('type'); // 'grade', 'attendance', 'behavior', 'general', 'payment', 'event'
            $table->string('priority')->default('normal'); // 'urgent', 'high', 'normal', 'low'
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Données supplémentaires en JSON
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['parent_id', 'is_read']);
            $table->index(['parent_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_notifications');
    }
};