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

        $storagePath = storage_path('app/public');
        $publicPath = public_path('storage');

        if (!file_exists($publicPath)) {
            @mkdir($publicPath, 0777, true);
        }

        // Copie les fichiers manuellement au boot
        foreach (glob($storagePath . '/*') as $file) {
            $fileName = basename($file);
            @copy($file, $publicPath . '/' . $fileName);
        }
        // Produit::observe(ProduitObserver::class);
        }
}