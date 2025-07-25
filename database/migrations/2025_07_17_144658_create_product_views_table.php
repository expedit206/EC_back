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
        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('produit_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            // $table->string('session_id')->nullable(); // Pour utilisateurs non connectés
            $table->index(['produit_id', 'user_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIndex(['produit_id', 'user_id']);

        Schema::dropIfExists('product_views');
    }
};