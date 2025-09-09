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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['critical', 'high', 'normal', 'low'])->default('normal');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled', 'overdue'])->default('pending');
            $table->enum('category', ['administrative', 'pedagogical', 'maintenance', 'event', 'urgent', 'other'])->default('other');
            
            // Assignation
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            
            // Dates
            $table->date('due_date')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            
            // Progression et validation
            $table->integer('progress')->default(0); // 0-100%
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('approved_at')->nullable();
            
            // Récurrence
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_type', ['daily', 'weekly', 'monthly', 'yearly'])->nullable();
            $table->integer('recurrence_interval')->nullable(); // Every N days/weeks/months
            $table->date('recurrence_end_date')->nullable();
            
            // Notifications
            $table->boolean('notification_sent')->default(false);
            $table->datetime('last_reminder_sent')->nullable();
            $table->integer('reminder_count')->default(0);
            
            // Gamification
            $table->integer('points')->default(10); // Points attribués pour cette tâche
            $table->integer('difficulty_level')->default(1); // 1-5
            
            // Metadata
            $table->json('attachments')->nullable(); // Fichiers joints
            $table->text('notes')->nullable(); // Notes additionnelles
            $table->json('checklist')->nullable(); // Sous-tâches ou checklist
            $table->boolean('is_template')->default(false); // Si c'est un modèle de tâche
            
            $table->timestamps();
            
            // Index pour les performances
            $table->index(['assigned_to', 'status']);
            $table->index(['due_date', 'status']);
            $table->index(['category', 'priority']);
            $table->index('is_recurring');
            $table->index('created_by');
        });

        // Table pour les commentaires sur les tâches
        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('comment');
            $table->json('attachments')->nullable();
            $table->timestamps();
            
            $table->index(['task_id', 'created_at']);
        });

        // Table pour l'historique des tâches
        Schema::create('task_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action'); // created, updated, status_changed, assigned, completed, etc.
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
            
            $table->index(['task_id', 'created_at']);
        });

        // Table pour les dépendances entre tâches
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('depends_on_task_id')->constrained('tasks')->onDelete('cascade');
            $table->enum('type', ['finish_to_start', 'start_to_start', 'finish_to_finish'])->default('finish_to_start');
            $table->timestamps();
            
            $table->unique(['task_id', 'depends_on_task_id']);
        });

        // Table pour les tâches assignées à plusieurs personnes
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->integer('progress')->default(0);
            $table->timestamps();
            
            $table->unique(['task_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        // Table pour les templates de tâches
        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', ['administrative', 'pedagogical', 'maintenance', 'event', 'urgent', 'other']);
            $table->enum('priority', ['critical', 'high', 'normal', 'low'])->default('normal');
            $table->integer('estimated_duration')->nullable(); // En minutes
            $table->json('default_checklist')->nullable();
            $table->integer('points')->default(10);
            $table->integer('difficulty_level')->default(1);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_templates');
        Schema::dropIfExists('task_assignees');
        Schema::dropIfExists('task_dependencies');
        Schema::dropIfExists('task_histories');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('tasks');
    }
};