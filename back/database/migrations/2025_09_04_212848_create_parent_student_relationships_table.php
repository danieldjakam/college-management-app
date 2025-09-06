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
        Schema::create('parent_student_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parent_guardians')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('relationship_type'); // 'father', 'mother', 'guardian', 'tutor', 'grandparent'
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('can_pick_up')->default(true);
            $table->boolean('emergency_contact')->default(false);
            $table->timestamps();
            
            $table->unique(['parent_id', 'student_id']);
            $table->index(['student_id', 'is_primary_contact']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_student_relationships');
    }
};