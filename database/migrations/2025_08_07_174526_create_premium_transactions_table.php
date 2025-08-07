<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePremiumTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('premium_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('type_abonnement')->nullable()->comment('Type d\'abonnement: mensuel ou annuel');
            $table->decimal('montant', 10, 2);
            $table->string('methode_paiement');
            $table->string('transaction_id_mesomb')->nullable();
            $table->string('statut');
            $table->timestamp('date_transaction');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('premium_transactions');
    }
}