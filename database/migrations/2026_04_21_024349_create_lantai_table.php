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
        Schema::create('lantai', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('gedung_id');
            $table->integer('nomor');

            $table->unique(['gedung_id', 'nomor'], 'gedung_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lantai');
    }
};
