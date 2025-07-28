<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('domain_id');
            $table->integer('coefficient')->default(1);
            $table->text('description')->nullable();
            $table->string('school_id')->default('GSBPL_001');
            $table->timestamps();

            $table->foreign('domain_id')->references('id')->on('domains')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subjects');
    }
};