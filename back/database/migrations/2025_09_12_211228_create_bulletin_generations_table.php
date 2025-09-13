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
        Schema::create('bulletin_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->constrained('bulletin_templates')->onDelete('cascade');
            $table->enum('period_type', ['sequence', 'trimester', 'annual', 'honor_roll']);
            $table->string('period_identifier'); // 'seq1', 'trim1', 'annual', etc.
            $table->string('file_path')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
            
            $table->index(['student_id', 'period_type', 'period_identifier'], 'bulletin_student_period_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulletin_generations');
    }
};
