<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletin_modifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->index();
            $table->enum('period_type', ['sequence', 'trimester', 'annual']);
            $table->string('period_identifier', 20);
            $table->json('modifications');
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('modified_by')->index();
            $table->timestamps();

            $table->unique(['student_id', 'period_type', 'period_identifier'], 'bulletin_mod_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletin_modifications');
    }
};
