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
                $table->id();
                $table->enum('statut', ['pending', 'active', 'rejected'])->default('pending');
                $table->decimal('gains', 10, 2)->default(0.00);
                $table->timestamp('date_activation')->nullable();
                
                $table->foreignId('parrain_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreignId('filleul_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['parrain_id', 'filleul_id']);
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