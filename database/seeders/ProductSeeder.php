<?php

namespace Database\Seeders;

use App\Models\Produit;
use App\Models\Category;
use App\Models\Commercant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Créer 100 produits, chacun associé à un commerçant et une catégorie
        $commercants = Commercant::all();
        $categories = Category::all();

        foreach ($commercants as $commercant) {
            Produit::factory(5)->create([
                'commercant_id' => $commercant->id,
                'category_id' => $categories->random()->id,
            ]);
        }
    }
}