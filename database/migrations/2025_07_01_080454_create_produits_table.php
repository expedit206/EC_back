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
        Schema::create('produits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('commercant_id')->references('id')->on('commercants')->onDelete('cascade');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->decimal('prix', 10, 2);
            $table->integer('quantite');
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('ville')->nullable();
            $table->json('photos')->nullable(); // Remplace photo_url par photos (JSON)
            $table->boolean('collaboratif')->default(false);
            $table->decimal('marge_min', 10, 2)->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};