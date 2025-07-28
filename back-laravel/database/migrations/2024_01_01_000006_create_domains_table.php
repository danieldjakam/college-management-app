<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->integer('coefficient')->default(1);
            $table->string('school_id')->default('GSBPL_001');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('domains');
    }
};