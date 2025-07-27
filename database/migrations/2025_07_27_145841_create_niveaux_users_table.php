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
        Schema::create('niveaux_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('niveau_id')
                ->constrained('niveaux_parrainages')
                ->onDelete('restrict');
            $table->dateTime('date_atteinte');
            $table->unsignedInteger('jetons_attribues')->nullable();
            $table->enum('statut', ['actif', 'inactif'])->default('actif');
            $table->unsignedInteger('nombre_filleuls_actuels')->default(0);
            // Nouveau champ
            $table->timestamps();

            $table->index('user_id');
            $table->index('niveau_id');
            $table->index('statut');
            $table->index(['user_id', 'niveau_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('niveaux_users');
    }
};