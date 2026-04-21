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
        Schema::table('ruangan_fasilitas', function (Blueprint $table) {
            $table->foreign(['ruangan_id'], 'ruangan_fasilitas_ibfk_1')->references(['id'])->on('ruangan')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['fasilitas_id'], 'ruangan_fasilitas_ibfk_2')->references(['id'])->on('fasilitas')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ruangan_fasilitas', function (Blueprint $table) {
            $table->dropForeign('ruangan_fasilitas_ibfk_1');
            $table->dropForeign('ruangan_fasilitas_ibfk_2');
        });
    }
};
