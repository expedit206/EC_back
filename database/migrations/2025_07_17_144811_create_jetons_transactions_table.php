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
        Schema::create('jetons_transactions', function (Blueprint $table) {
         $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('nombre_jetons')->unsigned(); // Nombre de jetons achetés
            $table->decimal('montant', 10, 2); // Montant total payé
            $table->string('methode_paiement');
            $table->string('phone_number')->nullable();
            $table->string('transaction_id_mesomb')->nullable();
            $table->string('statut');
            $table->timestamp('date_transaction');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jetons_transactions');
    }
};