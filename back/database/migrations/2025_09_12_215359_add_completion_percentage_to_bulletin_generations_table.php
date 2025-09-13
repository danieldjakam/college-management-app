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
        Schema::table('bulletin_generations', function (Blueprint $table) {
            $table->decimal('completion_percentage', 5, 2)->default(0)->after('generated_at');
            $table->boolean('is_complete')->default(false)->after('completion_percentage');
            $table->text('notes')->nullable()->after('is_complete'); // Pour des notes sur l'état du bulletin
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bulletin_generations', function (Blueprint $table) {
            $table->dropColumn(['completion_percentage', 'is_complete', 'notes']);
        });
    }
};
