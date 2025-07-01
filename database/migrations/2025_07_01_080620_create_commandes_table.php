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
        Schema::create('commandes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('acheteur_id');
            $table->uuid('produit_id');
            $table->uuid('commercant_id'); // Changé de vendeur_id à commercant_id
            $table->uuid('collaborateur_id')->nullable();
            $table->foreign('acheteur_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('produit_id')->references('id')->on('produits')->onDelete('cascade');
            $table->foreign('commercant_id')->references('id')->on('commercants')->onDelete('cascade');
            $table->foreign('collaborateur_id')->references('id')->on('users')->onDelete('set null');
            $table->enum('statut', ['en_attente', 'livrée', 'litige'])->default('en_attente');
            $table->decimal('montant_total', 10, 2);
            $table->enum('paiement_statut', ['en_attente', 'payé', 'remboursé'])->default('en_attente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};