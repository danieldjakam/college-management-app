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
        Schema::table('parent_notifications', function (Blueprint $table) {
            // Ajouter la colonne admin_id après student_id
            $table->foreignId('admin_id')->nullable()->after('student_id')->constrained('users')->onDelete('set null');
            
            // Supprimer la colonne data si elle existe (pas dans notre modèle)
            if (Schema::hasColumn('parent_notifications', 'data')) {
                $table->dropColumn('data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent_notifications', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropColumn('admin_id');
        });
    }
};
