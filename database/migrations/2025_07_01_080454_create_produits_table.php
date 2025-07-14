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
            $table->uuid('commercant_id');
            $table->foreign('commercant_id')->references('id')->on('commercants')->onDelete('cascade');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->decimal('prix', 10, 2);
            $table->integer('quantite');
            // $table->uuid('category_id');
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('ville')->nullable();
            $table->string('photo_url')->nullable();
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