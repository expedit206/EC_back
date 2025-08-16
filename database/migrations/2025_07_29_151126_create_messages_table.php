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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            // $table->foreignUuid('product_id')->references('id')->on('produits')->onDelete('set null');
            $table->foreignId('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreignUuid('product_id')->references('id')->on('produits')->onDelete('cascade')->nullable();
            $table->text('content');
            $table->boolean('is_read')->default(false);

            $table->uuid('product_id')->nullable()->default(null);
            $table->foreign('product_id')->references('id')->on('produits')->onDelete('set null');

            $table->timestamps();

            $table->index(['sender_id', 'receiver_id']);
        });
    }

    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};