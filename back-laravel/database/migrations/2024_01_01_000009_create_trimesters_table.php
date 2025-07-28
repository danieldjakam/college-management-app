<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('trimesters', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('school_year')->default('2024-2025');
            $table->boolean('is_active')->default(false);
            $table->string('school_id')->default('GSBPL_001');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('trimesters');
    }
};