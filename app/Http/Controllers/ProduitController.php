<?php
// app/Http/Controllers/ProduitController.php
namespace App\Http\Controllers;

use App\Models\Boost;
use Ramsey\Uuid\Uuid;
use App\Models\Produit;
use App\Models\Commercant;
use App\Models\ProductView;
use Illuminate\Http\Request;
use App\Jobs\RecordProductView;
use App\Models\ProductFavorite;
use App\Models\JetonsTransaction;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class ProduitController extends Controller
{

    public function index(Request $request)
    {
        // return response()->json(['message' => $request->all()]);
        $sort = $request->query('sort', 'default');
        $perPage = $request->query('per_page', 10) === 'all' ? null : $request->query('per_page', 10);
        $search = $request->query('search');
        $category = $request->query('category');
        $prixMin = $request->query('prix_min');
        $prixMax = $request->query('prix_max');
        $ville = $request->query('ville', $request->use?->ville);
        $collaboratif = $request->query('collaboratif');
        $user = $request->user;
        $page = $request->query('page', 1);

        // return response()->json(['message' => $request->all()]   );
        $viewedProductIds = $user ? ProductView::where('user_id', $user->id)->pluck('produit_id')->toArray() : [];
        $favoriteProductIds = $user ? ProductFavorite::where('user_id', $user->id)->pluck('produit_id')->toArray() : [];
        //retourner viewedProductIds
        // return response()->json(['viewedProductIds' => $viewedProductIds, 'favoriteProductIds' => $favoriteProductIds]);

        $query = Produit::query()
            ->with(['commercant', 'category', 'counts'])
            ->select('produits.*')
            ->leftJoin('product_counts', 'product_counts.produit_id', '=', 'produits.id')
            ->selectRaw(
                '
            COALESCE(product_counts.views_count, 0) as raw_views_count,
            COALESCE(product_counts.favorites_count, 0) as favorites_count,
            (0.24 * COALESCE(product_counts.views_count, 0) + 
             0.25 * COALESCE(product_counts.favorites_count, 0) + 
             0.26 * (CASE WHEN EXISTS (
                 SELECT 1 FROM boosts
                 WHERE boosts.produit_id = produits.id
                 AND boosts.statut = "actif"
                 AND boosts.end_date > NOW()
             ) THEN 1 ELSE 0 END) + 
             0.25 * (1 / (DATEDIFF(NOW(), produits.created_at) / 365 + 1))' .
                    (!empty($viewedProductIds) ? ' - 0.7 * (CASE WHEN produits.id IN (' . implode(',', array_fill(0, count($viewedProductIds), '?')) . ') THEN 1 ELSE 0 END)' : '') . ') as score',
                $viewedProductIds
            );

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($category) $query->where('category_id', $category);
        if ($prixMin) $query->where('prix', '>=', $prixMin);
        if ($prixMax) $query->where('prix', '<=', $prixMax);
        if ($ville) $query->where('ville', $ville);
        if ($collaboratif !== null) $query->where('collaboratif', $collaboratif === 'true');

        if ($sort === 'popular') {
            $query->orderBy('raw_views_count', 'desc');
        } elseif ($sort === 'favorites') {
            $query->orderBy('favorites_count', 'desc')->orderBy('score', 'desc');
        } else {
            $query->orderBy('score', 'desc')->orderBy('favorites_count', 'desc');
        }

        if ($perPage === null) {
            $produits = $query->get();
        } else {
            $produits = $query->paginate($perPage, ['*'], 'page', $page);
        }

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
        $produit = Produit::with(['commercant.user', 'category'])
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
    
    public function recordView(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:produits,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $produitId = $validated['product_id'];
        $userId = $validated['user_id'];
        $produit = Produit::findOrFail($produitId);

        // Vérifier si l'utilisateur a déjà vu ce produit
        $existingView = ProductView::where('produit_id', $produitId)
            ->where('user_id', $userId)
            ->first();

        if (!$existingView) {
            // Nouvelle vue, incrémenter views_count et enregistrer dans product_views
            $produit->counts()->updateOrCreate(
                ['produit_id' => $produitId],
                ['views_count' => \DB::raw('views_count + 1')]
            );

            ProductView::create([
                'produit_id' => $produitId,
                'user_id' => $userId,
            ]);

            return response()->json(['message' => 'Vue enregistrée']);
        } else {
            // Vue déjà enregistrée
            return response()->json(['message' => 'Vue déjà enregistrée'], 200);
        }
    }
    

    public function toggleFavorite($id, Request $request)
    {
        // Validation de l'ID
        $produit = Produit::findOrFail($id);

        // Récupérer l'utilisateur authentifié
        $user = $request->user;

        if (!$user) {
            return response()->json(['message' => 'Connexion requise'], 401);
        }

        $favorite = ProductFavorite::where('produit_id', $id)
            ->where('user_id', $user->id)
            ->first();
            // Supprimer le favori
            
            if ($favorite) {
                $favorite->delete();
                
          
                $produit->counts()->updateOrCreate(
                    ['produit_id' => $id],
                    ['favorites_count' => DB::raw('favorites_count-1')]
                );
                return response()->json(['message' => 'Produit retiré des favoris']);

        } else {
            // Ajouter le favori
            ProductFavorite::create([
                'produit_id' => $id,
                'user_id' => $user->id,
            ]);
            
            // Incrémenter favorites_count dans product_counts
            $produit->counts()->updateOrCreate(
                ['produit_id' => $id],
                ['favorites_count' => DB::raw('favorites_count + 1')]
            );
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