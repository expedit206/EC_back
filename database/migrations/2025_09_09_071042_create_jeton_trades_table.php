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
        Schema::create('jeton_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendeur_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('acheteur_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('offer_id')->constrained('jeton_offers')->onDelete('cascade');
            $table->integer('nombre_jetons')->unsigned(); // Quantité échangée
            $table->decimal('montant_total', 10, 2); // Montant payé par l'acheteur
            $table->decimal('commission_plateforme', 10, 2); // 5% du montant total
            $table->decimal('montant_net_vendeur', 10, 2); // Montant reçu par le vendeur (montant_total - commission)
            $table->string('methode_paiement')->default('mesomb');
            $table->string('transaction_id_mesomb_vendeur')->nullable(); // ID MeSomb pour le vendeur
            $table->string('transaction_id_mesomb_plateforme')->nullable(); // ID MeSomb pour la commission
            $table->string('statut')->default('en_attente'); // Ex. 'en_attente', 'confirme', 'annule'
            $table->timestamp('date_transaction')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jeton_trades');
    }
};