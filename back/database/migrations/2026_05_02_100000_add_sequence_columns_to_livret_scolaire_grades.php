<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('livret_scolaire_grades', function (Blueprint $table) {
            // Notes de sequences (EV1-EV4) et compositions (Comp1-Comp3)
            $table->decimal('ev1', 5, 2)->nullable()->after('modified_by');
            $table->decimal('ev2', 5, 2)->nullable()->after('ev1');
            $table->decimal('comp1', 5, 2)->nullable()->after('ev2');
            $table->decimal('ev3', 5, 2)->nullable()->after('comp1');
            $table->decimal('ev4', 5, 2)->nullable()->after('ev3');
            $table->decimal('comp2', 5, 2)->nullable()->after('ev4');
            $table->decimal('comp3', 5, 2)->nullable()->after('comp2');
        });
    }

    public function down(): void
    {
        Schema::table('livret_scolaire_grades', function (Blueprint $table) {
            $table->dropColumn(['ev1', 'ev2', 'comp1', 'ev3', 'ev4', 'comp2', 'comp3']);
        });
    }
};
