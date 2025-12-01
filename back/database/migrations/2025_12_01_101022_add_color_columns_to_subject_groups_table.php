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
        Schema::table('subject_groups', function (Blueprint $table) {
            $table->string('color', 20)->nullable()->after('description')->comment('Text color for group display');
            $table->string('bg_color', 20)->nullable()->after('color')->comment('Background color for group display');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_groups', function (Blueprint $table) {
            $table->dropColumn(['color', 'bg_color']);
        });
    }
};
