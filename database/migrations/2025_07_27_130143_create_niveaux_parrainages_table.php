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
            $table->unsignedInteger('filleuls_requis');
            $table->unsignedInteger('jetons_bonus');

            $table->json('avantages');
            $table->timestamps();
            $table->index('filleuls_requis');
        });

        // Insertion initiale des niveaux
        DB::table('niveaux_parrainages')->insert([
            ['nom' => 'Initié', 'emoji' => '🥉', 'filleuls_requis' => 1, 'jetons_bonus' => 3, 'avantages' => json_encode(['badge_depart']), 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Apporteur', 'emoji' => '🥈', 'filleuls_requis' => 20, 'jetons_bonus' => 10, 'avantages' => json_encode(['acces_progression']), 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Développeur', 'emoji' => '🥇', 'filleuls_requis' => 50, 'jetons_bonus' => 25, 'avantages' => json_encode(['badge_anime']), 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Stratège', 'emoji' => '💎', 'filleuls_requis' => 100, 'jetons_bonus' => 50, 'avantages' => json_encode(['tableau_classement']), 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Mentor', 'emoji' => '🔥', 'filleuls_requis' => 300, 'jetons_bonus' => 100, 'avantages' => json_encode(['mise_en_avant_locale']), 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Ambassadeur', 'emoji' => '👑', 'filleuls_requis' => 700, 'jetons_bonus' => 200, 'avantages' => json_encode(['badges_publics']), 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Légende', 'emoji' => '🛡️', 'filleuls_requis' => 1000, 'jetons_bonus' => 500, 'avantages' => json_encode(['statut_eternel', 'profil_en_or']), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('niveaux_parrainages');
    }
};