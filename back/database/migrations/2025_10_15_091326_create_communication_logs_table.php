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
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('general'); // general, attendance, academic, behavior, payment, event
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('send_to_type'); // all, class, student
            $table->integer('send_to_class_id')->nullable();
            $table->integer('send_to_student_id')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('successful_sends')->default(0);
            $table->integer('failed_sends')->default(0);
            $table->json('recipients_details')->nullable(); // Liste des destinataires avec status
            $table->boolean('has_attachments')->default(false);
            $table->integer('attachments_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
