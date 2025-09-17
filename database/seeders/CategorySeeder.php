<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Électronique',
            'Mode',
            'Maison',
            'Beauté',
            'Sport',
            'Jouets & Jeux',
            'Informatique',
            'Téléphonie',
            'Cuisine & Alimentation',
            'Santé & Bien-être',
            'Automobile',
            'Bricolage & Jardin',
            'Livres',
            'Musique & Instruments',
            'Films & Séries',
            'Accessoires',
            'Bijoux',
            'Vêtements Enfants',
            'Chaussures',
            'Art & Décoration',
            'Animaux & Accessoires',
            'Papeterie',
            'Voyages & Loisirs',
            'Équipements professionnels',
        ];

        foreach ($categories as $nom) {
            Category::create([
                'id' => Str::uuid(),
                'nom' => $nom,
            ]);
        }
    }
}