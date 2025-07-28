<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('class', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('section_id');
            $table->text('description')->nullable();
            $table->string('school_year')->default('2024-2025');
            $table->string('school_id')->default('GSBPL_001');
            $table->timestamps();

            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('class');
    }
};