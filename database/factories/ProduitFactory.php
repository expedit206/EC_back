<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Commercant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProduitFactory extends Factory
{
    protected $model = \App\Models\Produit::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'commercant_id' => Commercant::factory(), // Crée un Commercant associé
            // 'category_id' => Category::factory(), // Crée une Category associée
            'category_id' => Category::inRandomOrder()->first()?->id,
            'nom' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->paragraph(),
            'prix' => $this->faker->randomFloat(2, 500, 100000), // Prix entre 500 et 100000 FCFA
            'quantite' => $this->faker->randomFloat(5, 10), // Prix entre 500 et 100000 FCFA
            'photo_url' => $this->faker->imageUrl(300, 300, 'produits'), // URL d'image fictive
            'collaboratif' => $this->faker->boolean(30), // 30% de chance d'être collaboratif
            'marge_min' => $this->faker->randomFloat(2, 100, 5000), // Marge entre 100 et 5000 FCFA
            // 'stock' => $this->faker->numberBetween(0, 100),
            'ville' => $this->faker->randomElement(['Douala', 'Yaoundé', 'Bamenda', 'Buea', 'Garoua']),
        ];
    }
}
