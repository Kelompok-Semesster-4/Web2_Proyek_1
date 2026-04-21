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
        Schema::table('lantai', function (Blueprint $table) {
            $table->foreign(['gedung_id'], 'fk_lantai_gedung')->references(['id'])->on('gedung')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lantai', function (Blueprint $table) {
            $table->dropForeign('fk_lantai_gedung');
        });
    }
};
