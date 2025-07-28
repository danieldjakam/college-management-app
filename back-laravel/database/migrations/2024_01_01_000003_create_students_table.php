<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subname');
            $table->string('class_id');
            $table->enum('sex', ['m', 'f']);
            $table->string('fatherName')->nullable();
            $table->string('profession')->nullable();
            $table->date('birthday')->nullable();
            $table->string('birthday_place')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('school_year')->default('2024-2025');
            $table->enum('status', ['new', 'old'])->default('new');
            $table->enum('is_new', ['yes', 'no'])->default('yes');
            $table->string('school_id')->default('GSBPL_001');
            $table->decimal('inscription', 10, 2)->default(0);
            $table->decimal('first_tranch', 10, 2)->default(0);
            $table->decimal('second_tranch', 10, 2)->default(0);
            $table->decimal('third_tranch', 10, 2)->default(0);
            $table->decimal('graduation', 10, 2)->default(0);
            $table->decimal('assurance', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('class')->onDelete('cascade');
            $table->index(['class_id', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('students');
    }
};