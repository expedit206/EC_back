<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('niveaux_parrainages', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('emoji');
            $table->string('couleur'); // Nouvelle colonne pour la couleur
            $table->unsignedInteger('filleuls_requis');
            $table->unsignedInteger('jetons_bonus');
            $table->json('avantages');
            $table->timestamps();
            $table->index('filleuls_requis');
        });

        // Insertion initiale des niveaux avec couleurs
        DB::table('niveaux_parrainages')->insert([
            ['nom' => 'Débutant', 'emoji' => '🚀', 'couleur' => '#4CAF50', 'filleuls_requis' => 0, 'jetons_bonus' => 0, 'avantages' => json_encode(['bienvenue']), 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Initié', 'emoji' => '✨', 'couleur' => '#2196F3', 'filleuls_requis' => 1, 'jetons_bonus' => 5, 'avantages' => json_encode(['badge_depart']), 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Apporteur', 'emoji' => '🌱', 'couleur' => '#8BC34A', 'filleuls_requis' => 10, 'jetons_bonus' => 15, 'avantages' => json_encode(['acces_progression', 'bonus_petit_parrain']), 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Développeur', 'emoji' => '🌟', 'couleur' => '#FF9800', 'filleuls_requis' => 30, 'jetons_bonus' => 30, 'avantages' => json_encode(['badge_anime', 'reduction_paiement_5']), 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Stratège', 'emoji' => '🎯', 'couleur' => '#9C27B0', 'filleuls_requis' => 70, 'jetons_bonus' => 60, 'avantages' => json_encode(['tableau_classement', 'bonus_filleul_10']), 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Mentor', 'emoji' => '🔥', 'couleur' => '#FF5722', 'filleuls_requis' => 150, 'jetons_bonus' => 120, 'avantages' => json_encode(['mise_en_avant_locale', 'reduction_paiement_10']), 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Ambassadeur', 'emoji' => '🏅', 'couleur' => '#FFD700', 'filleuls_requis' => 300, 'jetons_bonus' => 250, 'avantages' => json_encode(['badges_publics', 'bonus_equipe']), 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Légende', 'emoji' => '🏆', 'couleur' => '#FFA500', 'filleuls_requis' => 1000, 'jetons_bonus' => 500, 'avantages' => json_encode(['statut_eternel', 'profil_en_or', 'reduction_paiement_20']), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('niveaux_parrainages');
    }
};      