<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Produit;

class UpdateProductCounts extends Command
{
    protected $signature = 'product:counts';
    protected $description = 'Mise à jour des compteurs de produits';

    public function handle()
    {
        Produit::withCount(['views', 'favorites'])->get()->each(function ($produit) {
            $produit->counts()->updateOrCreate(
                ['produit_id' => $produit->id],
                [
                    'views_count' => $produit->views_count,
                    'favorites_count' => $produit->favorites_count,
                ]
            );
        });

        $this->info('Compteurs de produits mis à jour avec succès.');
    }
}