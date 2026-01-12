<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Trimestre d'arrivee pour les nouveaux eleves (2 ou 3). Null = pas de reduction "nouveau".
            $table->unsignedTinyInteger('arrival_trimester')
                ->nullable()
                ->after('student_status')
                ->comment('Trimestre d\'arrivee du nouvel eleve (2 ou 3) pour reduction sur scolarite.');

            // Motif libre saisi par l'utilisateur
            $table->text('newcomer_discount_reason')
                ->nullable()
                ->after('arrival_trimester')
                ->comment('Motif libre de la reduction de scolarite pour nouvel eleve.');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['arrival_trimester', 'newcomer_discount_reason']);
        });
    }
};
