<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    // ajouter le faker 
    public function run(): void
    {
        
        // Créer 50 utilisateurs
        User::factory(5)->create();

        // Créer un utilisateur admin spécifique
        User::firstOrCreate(
            ['email' => 'aaa@aaa.com'],
            [
                // 'id' => \Illuminate\Support\Str::uuid(),
                'nom' => 'Admin Espace',
                'telephone' => '+237690000000',
                'ville' => 'Douala',
                'mot_de_passe' => \Illuminate\Support\Facades\Hash::make('aaaaaaaa'),
                // 'role' => 'admin',
                'premium' => true,
                'parrain_id' => null,
            ]
        );

        // Associer quelques utilisateurs à un parrain
        $users = User::all();
        $users->each(function ($user) use ($users) {
            if (Faker::create()->boolean(30) // 30% de chance d'avoir un parrain    
) { 
                
                // 30% de chance d'avoir un parrain
                $user->parrain_id = $users->random()->id;
                $user->save();
            }
        });
    }
}