<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => $this->faker->name(),
            'telephone' => $this->faker->unique()->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'ville' => $this->faker->randomElement(['Douala', 'Yaoundé', 'Bamenda', 'Buea', 'Garoua']),
            'mot_de_passe' => Hash::make('aaaaaaaa'), // Mot de passe par défaut
            // 'role' => $this->faker->randomElement(['client', 'admin']),
            'premium' => $this->faker->boolean(20), // 20% de chance d'être premium
            'parrain_id' => null, // Peut être mis à jour dans le seeder
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}