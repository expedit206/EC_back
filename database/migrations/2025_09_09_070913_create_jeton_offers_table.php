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
        Schema::create('jeton_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Vendeur
            $table->foreignId('wallet_id')->nullable()->constrained('wallets')->onDelete('set null');
            $table->integer('nombre_jetons')->unsigned(); // Quantité de jetons proposés
            $table->decimal('prix_unitaire', 10, 2); // Prix par jeton en FCFA
            $table->decimal('total_prix', 10, 2); // Prix total (nombre_jetons * prix_unitaire)
            $table->string('statut')->default('disponible'); // Ex. 'disponible', 'en_cours', 'vendu'
            $table->timestamp('date_creation')->useCurrent();
            $table->timestamp('date_expiration')->nullable(); // Optionnel pour limiter la durée
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jeton_offers');
    }
};