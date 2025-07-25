<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\ProductFavorite;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProduitController;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return response()->json(['categories' => $categories]);
    }



    public function relatedProduct(Produit $produit, Request $request)
    {
        $categoryId = $request->query('category_id', $produit->category_id); // Utilise la catégorie du produit si non spécifiée
        $user = $request->user;

        // Créer une nouvelle requête avec les paramètres nécessaires
        $customRequest = Request::create('', '', [
            'category' => $categoryId,
        ]);
         $request->merge([
            'category' => $categoryId,
            
        ]);

        // Instancier ProduitController et appeler index
        $produitController = new ProduitController();
        $response = $produitController->index($request  );

        // Décoder la réponse JSON
        $produits = json_decode($response->getContent(), true);

        // Si paginé, extraire les résultats (produits)
        $produits = $produits['data'] ?? $produits;

       
     

        return response()->json(['produits' => $produits]);
    }
}