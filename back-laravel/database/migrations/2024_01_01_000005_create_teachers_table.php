<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subname');
            $table->enum('sex', ['m', 'f']);
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->date('birthday')->nullable();
            $table->string('birthday_place')->nullable();
            $table->string('profession')->nullable();
            $table->string('class_id')->nullable();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('school_id')->default('GSBPL_001');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('class')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('teachers');
    }
};