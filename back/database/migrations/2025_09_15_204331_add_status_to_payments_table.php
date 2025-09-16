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
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'validated', 'cancelled'])->default('pending')->after('notes');
            $table->string('cancellation_reason')->nullable()->after('status');
            $table->timestamp('status_updated_at')->nullable()->after('cancellation_reason');
            $table->foreignId('status_updated_by')->nullable()->constrained('users')->after('status_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['status_updated_by']);
            $table->dropColumn(['status', 'cancellation_reason', 'status_updated_at', 'status_updated_by']);
        });
    }
};