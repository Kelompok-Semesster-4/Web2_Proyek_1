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
        Schema::table('log_status', function (Blueprint $table) {
            $table->foreign(['peminjaman_id'], 'log_status_ibfk_1')->references(['id'])->on('peminjaman')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['status_id'], 'log_status_ibfk_2')->references(['id'])->on('status_peminjaman')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['diubah_oleh'], 'log_status_ibfk_3')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_status', function (Blueprint $table) {
            $table->dropForeign('log_status_ibfk_1');
            $table->dropForeign('log_status_ibfk_2');
            $table->dropForeign('log_status_ibfk_3');
        });
    }
};
