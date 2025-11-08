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
        Schema::table('subjects', function (Blueprint $table) {
            // Ajouter la colonne section_id
            $table->unsignedBigInteger('section_id')->nullable()->after('group');

            // Ajouter la clé étrangère vers la table sections
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null');

            // Commentaire pour expliquer l'utilisation
            $table->comment('section_id permet d\'avoir des groupes différents par section (Francophone, Technique, Anglophone)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Supprimer la clé étrangère d'abord
            $table->dropForeign(['section_id']);

            // Puis supprimer la colonne
            $table->dropColumn('section_id');
        });
    }
};
