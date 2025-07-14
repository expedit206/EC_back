<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('parrainages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('parrain_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('filleul_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('niveau')->default(1);
            $table->decimal('recompense', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parrainages');
    }
};