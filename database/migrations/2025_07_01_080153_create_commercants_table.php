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
        Schema::create('commercants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('ville');
            $table->string('telephone')->nullable(); // Ajout du champ téléphone
            $table->string('email')->nullable();     // Ajout du champ email
            $table->integer('active_products')->default(0); // Champ optionnel pour les produits actifs
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commercants');
    }
};