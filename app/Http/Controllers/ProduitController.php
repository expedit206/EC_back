<?php
// app/Http/Controllers/ProduitController.php
namespace App\Http\Controllers;

use Ramsey\Uuid\Uuid;
use App\Models\Produit;
use App\Models\Commercant;
use App\Models\ProductView;
use Illuminate\Http\Request;
use App\Models\ProductFavorite;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class ProduitController extends Controller
{
    public function index(Request $request)
    {
        // return response()->json(['message' => 'Invalid request']);
        $sort = $request->query('sort', 'random');
        $perPage = $request->query('per_page', 10);
        $search = $request->query('search');
        $category = $request->query('category');
        $prixMin = $request->query('prix_min');
        $prixMax = $request->query('prix_max');
        $ville = $request->query('ville');
        $collaboratif = $request->query('collaboratif');
        $user = $request->user;
//return perpage
// return response()->json(['per_page' => $perPage]);


        $query = Produit::query()
            ->with(['commercant', 'category'])
            ->withCount('favorites')    
            ->withCount('views');
        // Filtres
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($category) {
            $query->where('category_id', $category);
        }
        if ($prixMin) {
            $query->where('prix', '>=', $prixMin);
        }
        if ($prixMax) {
            $query->where('prix', '<=', $prixMax);
        }
        if ($ville) {
            $query->where('ville', $ville);
        }
        if ($collaboratif !== null) {
            $query->where('collaboratif', $collaboratif === 'true');
        }

        // Tri
        $query->inRandomOrder();
        if ($sort === 'popular') {
            $query->orderBy('views_count', 'desc');
        } elseif ($sort === 'favorites') {
            $query->orderBy('favorites_count', 'desc');
        }

        if($perPage == 'all'){
            // return response()->json(['per_page' => $perPage]);

            $produits = $query->get();

            $favoritedProductIds = $user
                ? ProductFavorite::where('user_id', $user->id)->pluck('produit_id')->toArray()
                : [];
            // Ajouter is_favorited_by à chaque produit
            $produits->each(function ($produit) use ($favoritedProductIds) {
                $produit->is_favorited_by = in_array($produit->id, $favoritedProductIds);
            });
        }else{

            $produits = $query->paginate($perPage);
            $favoritedProductIds = $user
                ? ProductFavorite::where('user_id', $user->id)->pluck('produit_id')->toArray()
                : [];
                
                // Ajouter is_favorited_by à chaque produit
                $produits->getCollection()->each(function ($produit) use ($favoritedProductIds) {
                $produit->is_favorited_by = in_array($produit->id, $favoritedProductIds);
            });
        }

        // Charger les IDs des produits favoris par l'utilisateur

        return response()->json($produits);
    }

    public function show($id, Request $request)
    {
        $user = $request->user;
        $produit = Produit::with(['commercant', 'category'])
            ->withCount('favorites')
            ->findOrFail($id);

        // Ajouter is_favorited_by
        $produit->is_favorited_by = $user
            ? ProductFavorite::where('user_id', $user->id)->where('produit_id', $id)->exists()
            : false;

        // Vérifier si l'utilisateur a déjà vu le produit
        if ($user) {
            $viewExists = ProductView::where('produit_id', $id)
                ->where('user_id', $user->id)
                ->exists();

            if (!$viewExists) {
                ProductView::create([
                    'produit_id' => $id,
                    'user_id' => $user->id,
                ]);
                Redis::incr("produit:views:{$id}");
            }
        }

        return response()->json(['produit' => $produit]);
    }

    public function toggleFavorite($id, Request $request)
    {
        $produit = Produit::findOrFail($id);
        $user = $request->user;
        
        if (!$user) {
            return response()->json(['message' => 'Connexion requise'], 401);
        }
        
        $favorite = ProductFavorite::where('produit_id', $id)
        ->where('user_id', $user->id)
        ->first();
        
        if ($favorite) {
            $favorite->delete();
            return response()->json(['message' => 'Produit retiré des favoris']);
        } else {
            ProductFavorite::create([
                'produit_id' => $id,
                'user_id' => $user->id,
            ]);
            // return response()->json(['produit' => $produit]);
            return response()->json(['message' => 'Produit ajouté aux favoris']);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix' => 'required|numeric|min:0',
            'quantite' => 'required|integer|min:0',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'ville' => 'nullable|string',
            'photo_url' => 'nullable|string',
            'collaboratif' => 'boolean',
            'marge_min' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user;
        $commercant = Commercant::where('user_id', $user->id)->first();

        if (!$commercant) {
            return response()->json(['message' => 'Vous devez être un commerçant pour créer un produit'], 403);
        }

        $produit = new Produit([
            'id' => Uuid::uuid4()->toString(),
            'commercant_id' => $commercant->id,
            'nom' => $request->nom,
            'description' => $request->description,
            'prix' => $request->prix,
            'quantite' => $request->quantite,
            'category_id' => $request->category_id,
            'ville' => $request->ville,
            'photo_url' => $request->photo_url,
            'collaboratif' => $request->collaboratif ?? false,
            'marge_min' => $request->marge_min,
        ]);
        $produit->save();

        // Charger les relations et ajouter is_favorited_by
        $produit->load(['commercant', 'category'])->loadCount('favorites');
        $produit->is_favorited_by = $user
            ? ProductFavorite::where('user_id', $user->id)->where('produit_id', $produit->id)->exists()
            : false;

        return response()->json(['message' => 'Produit créé', 'produit' => $produit], 201);
    }

    public function related($id, Request $request)
    {
        $produit = Produit::findOrFail($id);
        $limit = $request->query('limit', 4);
        $categoryId = $request->query('category_id');
        $user = $request->user;

        $produits = Produit::query()
            ->where('id', '!=', $id)
            ->where('category_id', $categoryId)
            ->with(['commercant', 'category'])
            ->withCount('favorites')
            ->inRandomOrder()
            ->take($limit)
            ->get();

        // Charger les IDs des produits favoris par l'utilisateur
        $favoritedProductIds = $user
            ? ProductFavorite::where('user_id', $user->id)->pluck('produit_id')->toArray()
            : [];

        // Ajouter is_favorited_by à chaque produit
        $produits->each(function ($produit) use ($favoritedProductIds) {
            $produit->is_favorited_by = in_array($produit->id, $favoritedProductIds);
        });

        return response()->json(['produits' => $produits]);
    }
}