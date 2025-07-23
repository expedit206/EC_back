<?php
// app/Http/Controllers/ProduitController.php
namespace App\Http\Controllers;

use App\Models\Boost;
use Ramsey\Uuid\Uuid;
use App\Models\Produit;
use App\Models\Commercant;
use App\Models\ProductView;
use Illuminate\Http\Request;
use App\Models\ProductFavorite;
use App\Models\JetonsTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class ProduitController extends Controller
{
    public function index(Request $request)
    {
        // return response()->json(['message' => Produit::paginate('10')]);
        $sort = $request->query('sort', 'default'); // Changé de 'random' à 'default'
        $perPage = $request->query('per_page', 10) === 'all' ? null : $request->query('per_page', 10);
        $search = $request->query('search');
        $category = $request->query('category');
        $prixMin = $request->query('prix_min');
        $prixMax = $request->query('prix_max');
        $ville = $request->query('ville', $request->user()?->ville); // Ville de l'utilisateur par défaut
        $collaboratif = $request->query('collaboratif');
        $user = $request->user; // Utilisateur connecté
        $page = $request->query('page', 1);

        // Récupérer les produits vus et favoris de l'utilisateur (si connecté) via product_views
        $viewedProductIds = $user ? ProductView::where('user_id', $user->id)->pluck('produit_id')->toArray() : [];
        $favoriteProductIds = $user ? ProductFavorite::where('user_id', $user->id)->pluck('produit_id')->toArray() : [];

        $query = Produit::query()
            ->with(['commercant', 'category'])
            ->select('produits.*')
            ->selectRaw(
                '
            (SELECT COUNT(*) FROM product_views WHERE product_views.produit_id = produits.id) as views_count,
            (SELECT COUNT(*) FROM product_favorites WHERE product_favorites.produit_id = produits.id) as favorites_count,
            (0.24 * (SELECT COUNT(*) FROM product_views WHERE product_views.produit_id = produits.id) + 
             0.25 * (SELECT COUNT(*) FROM product_favorites WHERE product_favorites.produit_id = produits.id) + 
             0.26 * (CASE WHEN EXISTS (
                 SELECT 1 FROM boosts
                 WHERE boosts.produit_id = produits.id
                 AND boosts.statut = "actif"
                 AND boosts.end_date > NOW()
             ) THEN 1 ELSE 0 END) + 
             0.25 * (1 / (DATEDIFF(NOW(), produits.created_at) / 365 + 1))' .
                    (!empty($viewedProductIds) ? ' - 0.7 * (CASE WHEN produits.id IN (' . implode(',', array_fill(0, count($viewedProductIds), '?')) . ') THEN 1 ELSE 0 END)' : '') . ') as score',
                $viewedProductIds // Bindings uniquement si $viewedProductIds n'est pas vide
            )
            // ->whereNotIn('id', $viewedProductIds) // Exclure les produits déjà vus (optionnel)
            ->when($user, function ($query) use ($user, $ville) {
                // Si commerçant, prioriser ses produits ou ceux de sa région
                if ($user->is_commercant ?? false) { // Hypothèse : colonne is_commercant
                    $query->where(function ($q) use ($user, $ville) {
                        $q->where('commercant_id', $user->id)->orWhere('ville', $ville);
                    });
                }
            });

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

        // Gestion du tri alternatif
        if ($sort === 'popular') {
            $query->orderBy('views_count', 'desc');
        } elseif ($sort === 'favorites') {
            $query->orderBy('favorites_count', 'desc')->orderBy('score', 'desc');
        } else {
      
            $query->orderBy('score', 'desc')->orderBy('id'); // Fallback si pas de favoris
        }

        // Pagination ou récupération complète
        if ($perPage === null) {
            $produits = $query->get();
        } else {
            $produits = $query->paginate($perPage, ['*'], 'page', $page);
        }

        // Ajout des informations sur les favoris et boosts
        $favoritedProductIds = $user ? ProductFavorite::where('user_id', $user->id)->pluck('produit_id')->toArray() : [];

        if ($perPage === null) {
            $produits->each(function ($produit) use ($favoritedProductIds) {
                $produit->is_favorited_by = in_array($produit->id, $favoritedProductIds);
                $boost = Boost::where('produit_id', $produit->id)
                    ->where('statut', 'actif')
                    ->where('end_date', '>', now())
                    ->latest('end_date')
                    ->first();
                $produit->boosted_until = $boost ? $boost->end_date : null;
            });
        } else {
            $produits->getCollection()->each(function ($produit) use ($favoritedProductIds) {
                $produit->is_favorited_by = in_array($produit->id, $favoritedProductIds);
                $boost = Boost::where('produit_id', $produit->id)
                    ->where('statut', 'actif')
                    ->where('end_date', '>', now())
                    ->latest('end_date')
                    ->first();
                $produit->boosted_until = $boost ? $boost->end_date : null;
            });
        }

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

        $produit->boosted_until = $produit->boosts->first()?->end_date;

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

    public function boost(Request $request, $id)
    {
        $produit = Produit::findOrFail($id);
        
        if ($produit->commercant_id !== $request->user->commercant->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        // Vérifier s'il existe déjà un boost actif
        $activeBoost = Boost::where('produit_id', $id)
        ->where('statut', 'actif')
            ->where('end_date', '>', now())
            ->first();

            if ($activeBoost) {
                return response()->json(['message' => 'Un boost est déjà actif pour ce produit'], 400);
            }
            
            // Coût du boost (ex. : 50 Jetons pour 3 jours)
        $coutJetons = 50;
        $user = $request->user; 
        
        // return response()->json(['message' => $user->jetons, ]);
        if ($user->jetons < $coutJetons) {
            return response()->json(['message' => 'Pas assez de Jetons'], 400);
        }

        // Créer le boost
        $boost = Boost::create([
            'user_id' => $request->user->id,
            'produit_id' => $id,
            'type' => 'produit',
            'start_date' => now(),
            'end_date' => now()->addDays(3), // 3 jours par défaut
            'statut' => 'actif',
            'cout_jetons' => $coutJetons,
        ]);

        // Déduire les Jetons et enregistrer la transaction
        $user->jetons -= $coutJetons;
        $user->save();

        JetonsTransaction::create([
            'user_id' => $request->user->id,
            'type' => 'depense',
            'montant' => -$coutJetons,
            'description' => "Dépense de {$coutJetons} Jetons pour booster le produit #{$id}",
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Produit boosté pour 3 jours',
            'data' => $boost,
        ]);
    }
}