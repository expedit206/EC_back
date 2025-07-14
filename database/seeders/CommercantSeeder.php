<?php

namespace Database\Seeders;

use App\Models\Commercant;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommercantSeeder extends Seeder
{
    public function run(): void
    {
        // Sélectionner 20 utilisateurs aléatoires pour devenir commerçants
        $users = User::inRandomOrder()->take(10)->get();
        foreach ($users as $user) {
            Commercant::factory()->create([
                'user_id' => $user->id,
            ]);
        }
    }
}