<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Commercant>
 */
class CommercantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(), // Crée un User associé
            'nom' => $this->faker->company(),
            'ville' => $this->faker->randomElement(['Douala', 'Yaoundé', 'Bamenda', 'Buea', 'Garoua']),
            'description' => $this->faker->optional()->sentence(),
            // 'revenus' => $this->faker->randomFloat(2, 0, 1000000), // Revenus entre 0 et 1M FCFA
            // 'produits_vendus' => $this->faker->numberBetween(0, 100),
        ];
    }
}