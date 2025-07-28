<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('subject_id');
            $table->string('sequence_id')->nullable();
            $table->string('trimester_id')->nullable();
            $table->decimal('note', 4, 2);
            $table->enum('note_type', ['devoir', 'composition', 'exam'])->default('devoir');
            $table->integer('coefficient')->default(1);
            $table->string('school_id')->default('GSBPL_001');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('sequence_id')->references('id')->on('sequences')->onDelete('set null');
            $table->foreign('trimester_id')->references('id')->on('trimesters')->onDelete('set null');
            
            $table->index(['student_id', 'subject_id', 'sequence_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notes');
    }
};