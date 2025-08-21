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
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')
                  ->comment('L\'utilisateur membre du personnel');
            $table->foreignId('supervisor_id')->constrained('users')->onDelete('cascade')
                  ->comment('Le surveillant qui enregistre la présence');
            $table->foreignId('school_year_id')->constrained('school_years')->onDelete('cascade')
                  ->comment('L\'année scolaire');
            
            // Informations de présence
            $table->date('attendance_date')->comment('Date de présence');
            $table->timestamp('scanned_at')->comment('Moment précis du scan QR');
            $table->boolean('is_present')->default(true)->comment('Présent ou absent');
            $table->enum('event_type', ['entry', 'exit', 'auto'])->default('auto')
                  ->comment('Type d\'événement: entrée, sortie ou automatique');
            $table->enum('staff_type', ['teacher', 'accountant', 'supervisor', 'admin'])
                  ->comment('Type de personnel');
            
            // Calculs de temps de travail
            $table->decimal('work_hours', 5, 2)->nullable()
                  ->comment('Heures de travail effectuées');
            $table->integer('late_minutes')->default(0)
                  ->comment('Minutes de retard');
            $table->integer('early_departure_minutes')->default(0)
                  ->comment('Minutes de départ anticipé');
            
            // Notes additionnelles
            $table->text('notes')->nullable()->comment('Notes ou observations');
            
            $table->timestamps();
            
            // Index pour optimiser les requêtes
            $table->index(['user_id', 'attendance_date']);
            $table->index(['staff_type', 'attendance_date']);
            $table->index(['school_year_id', 'attendance_date']);
            $table->index(['supervisor_id', 'attendance_date']);
            
            // Contrainte unique pour éviter les doublons
            $table->unique(['user_id', 'attendance_date', 'event_type'], 'unique_user_date_event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_attendances');
    }
};