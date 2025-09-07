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
        Schema::create('boosts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->foreignUuid('produit_id')->nullable()->constrained('produits')->onDelete('set null');
        $table->timestamp('start_date');
        $table->timestamp('end_date')->nullable();
        $table->string('type');
        $table->string('statut')->default('actif');
        $table->integer('cout_jetons')->default(50); // Coût par défaut
        $table->integer('duration_days')->nullable(); // Durée en jours
        $table->integer('target_views')->nullable(); // Objectif de vues
        $table->index(['produit_id', 'statut', 'end_date']);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIndex(['produit_id', 'statut', 'end_date']);
        Schema::dropIfExists('boosts');
        
    }
};