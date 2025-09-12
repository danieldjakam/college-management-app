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
            $table->boolean('whatsapp_sent')->default(false)->after('is_read');
            $table->timestamp('whatsapp_sent_at')->nullable()->after('whatsapp_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent_notifications', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_sent', 'whatsapp_sent_at']);
        });
    }
};
