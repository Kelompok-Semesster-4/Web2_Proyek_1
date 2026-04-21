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
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->foreign(['user_id'], 'peminjaman_ibfk_1')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['ruangan_id'], 'peminjaman_ibfk_2')->references(['id'])->on('ruangan')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['status_id'], 'peminjaman_ibfk_3')->references(['id'])->on('status_peminjaman')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->dropForeign('peminjaman_ibfk_1');
            $table->dropForeign('peminjaman_ibfk_2');
            $table->dropForeign('peminjaman_ibfk_3');
        });
    }
};
