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
        Schema::create('ruangan_fasilitas', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('ruangan_id')->nullable()->index('ruangan_id');
            $table->integer('fasilitas_id')->nullable()->index('fasilitas_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ruangan_fasilitas');
    }
};
