<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subject_groups', function (Blueprint $table) {
            $table->string('header')->after('code')->nullable(); // Ex: "GROUPE A" ou "GROUP A"
            $table->string('header_en')->after('header')->nullable(); // Version anglaise
        });

        // Mettre à jour les valeurs par défaut
        DB::table('subject_groups')->where('code', 'A')->update([
            'header' => 'GROUPE A',
            'header_en' => 'GROUP A'
        ]);
        DB::table('subject_groups')->where('code', 'B')->update([
            'header' => 'GROUPE B',
            'header_en' => 'GROUP B'
        ]);
        DB::table('subject_groups')->where('code', 'C')->update([
            'header' => 'GROUPE C',
            'header_en' => 'GROUP C'
        ]);
        DB::table('subject_groups')->where('code', 'D')->update([
            'header' => 'GROUPE D',
            'header_en' => 'GROUP D'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_groups', function (Blueprint $table) {
            $table->dropColumn(['header', 'header_en']);
        });
    }
};
