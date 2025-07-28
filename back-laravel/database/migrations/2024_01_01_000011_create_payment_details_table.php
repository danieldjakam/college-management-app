<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->decimal('amount', 10, 2);
            $table->string('payment_type')->default('scolarite');
            $table->text('description')->nullable();
            $table->string('operator_id')->nullable();
            $table->string('school_id')->default('GSBPL_001');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->index(['student_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_details');
    }
};