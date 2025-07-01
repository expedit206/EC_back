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
        Schema::create('boutiques', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('commercant_id');
            $table->foreign('commercant_id')->references('id')->on('commercants')->onDelete('cascade');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('ville');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boutiques');
    }
};