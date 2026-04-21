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
        Schema::create('log_status', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('peminjaman_id')->index('peminjaman_id');
            $table->integer('status_id')->index('status_id');
            $table->integer('diubah_oleh')->nullable()->index('diubah_oleh');
            $table->timestamp('waktu')->nullable()->useCurrent();
            $table->text('catatan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_status');
    }
};
