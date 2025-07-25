<?php

namespace App\Providers;

use App\Models\Produit;
use App\Observers\ProduitObserver;
use Illuminate\Support\ServiceProvider;
use App\Console\Commands\UpdateProductCounts;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->commands([
            UpdateProductCounts::class,
        ]);  
      }
          
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Produit::observe(ProduitObserver::class);
        }
}