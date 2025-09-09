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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('phone_number')->unique()->regex('/^6[0-9]{8}$/'); // 9 chiffres commençant par 6
            $table->enum('payment_service', ['ORANGE', 'MTN'])->default('ORANGE');
            $table->boolean('is_verified')->default(false); // Optionnel : pour vérifier le numéro
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
