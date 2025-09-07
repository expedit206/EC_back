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
use App\Models\JetonTransaction;
use App\Models\JetonsTransaction;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class ProduitController extends Controller
{
public function index(Request $request)
    {
        $user = $request->user(); // Disponible grâce au middleware

        $sort = $request->query('sort', 'default');
        $perPage = $request->query('per_page', 10) === 'all' ? null : (int)$request->query('per_page', 10);
        $search = $request->query('search');
        $category = $request->query('category');
        $prixMin = $request->query('prix_min');
        $prixMax = $request->query('prix_max');
        $ville = $request->query('ville');
        $collaboratif = filter_var($request->query('collaboratif'), FILTER_VALIDATE_BOOLEAN, ['default' => null]);
        $page = (int)$request->query('page', 1);

        $query = Produit::query()
            ->with(['commercant', 'category', 'counts'])
            ->leftJoin('product_counts', 'product_counts.produit_id', '=', 'produits.id')
            ->leftJoin('boosts', function ($join) {
                $join->on('boosts.produit_id', '=', 'produits.id')
                     ->where('boosts.statut', 'actif');
            });

        // Récupérer les IDs des produits vus et favoris par l'utilisateur
        $viewedProductIds = $user ? ProductView::where('user_id', $user->id)->pluck('produit_id')->toArray() : [];
        $favoriteProductIds = $user ? ProductFavorite::where('user_id', $user->id)->pluck('produit_id')->toArray() : [];

        // Appliquer les filtres
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($category) $query->where('category_id', $category);
        if ($prixMin) $query->where('prix', '>=', (float)$prixMin);
        if ($prixMax) $query->where('prix', '<=', (float)$prixMax);
        if ($ville) $query->where('ville', $ville);
        if ($collaboratif !== null) $query->where('collaboratif', $collaboratif);

        // Calculer le score dynamisé
        $query->select('produits.*')
              ->selectRaw('
                  COALESCE(product_counts.views_count, 0) as raw_views_count,
                  COALESCE(product_counts.favorites_count, 0) as favorites_count,
                  (
                      0.24 * COALESCE(product_counts.views_count, 0) +
                      0.25 * COALESCE(product_counts.favorites_count, 0) +
                      0.26 * (
                          CASE WHEN boosts.id IS NOT NULL THEN
                              LEAST(1, NULLIF(boosts.target_views, 0)  /COALESCE(product_counts.views_count, 0) ) * 
                              (1 - GREATEST(0, LEAST(1, DATEDIFF(NOW(), boosts.start_date) / DATEDIFF(boosts.end_date, boosts.start_date))))
                          ELSE 0 
                          END
                      ) +
                      0.25 * (1 / (DATEDIFF(NOW(), produits.created_at) / 365 + 1))
                  ) as score
              ')
              ->withCasts(['score' => 'float']);

        // Appliquer la pénalité pour les produits vus
        if (!empty($viewedProductIds)) {
            $query->addSelect(DB::raw("CASE WHEN produits.id IN (" . implode(',', array_map('intval', $viewedProductIds)) . ") THEN -0.7 ELSE 0 END as view_penalty"))
                  ->orderByRaw('score + view_penalty DESC');
        } else {
            $query->orderBy('score', 'desc');
        }

        // Gestion du tri
        switch ($sort) {
            case 'popular':
                $query->orderBy('raw_views_count', 'desc');
                break;
            case 'favorites':
                $query->orderBy('favorites_count', 'desc')
                      ->orderBy('score', 'desc');
                break;
            default:
                $query->orderBy('score', 'desc')
                      ->orderBy('favorites_count', 'desc');
                break;
        }

        // Pagination
        if ($perPage === null) {
            $produits = $query->get();
        } else {
            $produits = $query->paginate($perPage, ['*'], 'page', $page);
        }

        // Ajouter les propriétés dynamiques
        $collection = $perPage === null ? $produits : $produits->getCollection();
        $collection->each(function ($produit) use ($user, $favoriteProductIds) {
            $produit->is_favorited_by = $user && in_array($produit->id, $favoriteProductIds);
            $boost = $produit->boosts->first();
            $produit->boosted_until = $boost?->end_date ?? null;
            unset($produit->boosts);
        });


        if($request->user()->id==3)
{

            // Vérifier et désactiver les boosts expirés ou atteignant l'objectif
            $activeBoosts = Boost::where('statut', 'actif')->get();
            foreach ($activeBoosts as $boost) {
                $produit = Produit::with('counts')->find($boost->produit_id);
                if ($produit && $produit->counts) {
                    $currentViews = $produit->counts->views_count ?? 0;
                    if ($currentViews >= ($boost->target_views ?? 0) || ($boost->end_date && $boost->end_date < now('Africa/Douala'))) {
                        $boost->update(['statut' => 'inactif']);
                    
                    }
                }
            }
}
            

        return response()->json($produits);


        
    }


    public function show($id, Request $request)
    {
        $user = $request->user();
        $produit = Produit::with(['commercant.user', 'category'])
            ->with('counts')
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

                // Redis::incr("produit:views:{$id}");
            }
        }
        $produit->commercant->rating = $produit->commercant->average_rating; // Utilise l'attribut calculé


        $produit->boosted_until = $produit->boosts->first()?->end_date;

        return response()->json(['produit' => $produit->load('counts')]);
    }

    public function recordView(Request $request)
    {

        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:produits,id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        // return response()->json(['message' => $request->all()]);
        
        
        $produitId = $validated['product_id'];
        $userId = $validated['user_id'] ?? null ;
        $produit = Produit::findOrFail($produitId);
        
        if(!$request->user()){
        
            $produit->counts()->updateOrCreate(
                ['produit_id' => $produitId],
                ['views_count' => \DB::raw('views_count + 1')]
            );

            return response()->json(['message' => 'Vue enregistrée1',
        'user'=> $request->all()
        ]);
        }
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
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'Connexion requise'], 401);
    }

    $favorite = ProductFavorite::where('produit_id', $id)
        ->where('user_id', $user->id)
        ->first();

    if ($favorite) {
        // Supprimer le favori
        $favorite->delete();

        $produit->counts()->updateOrCreate(
            ['produit_id' => $id],
            ['favorites_count' => DB::raw('favorites_count - 1')]
        );
        $message = 'Produit retiré des favoris';
        $isFavoritedBy = false;
    } else {
        // Ajouter le favori
        ProductFavorite::create([
            'produit_id' => $id,
            'user_id' => $user->id,
        ]);

        $produit->counts()->updateOrCreate(
            ['produit_id' => $id],
            ['favorites_count' => DB::raw('favorites_count + 1')]
        );
        $message = 'Produit ajouté aux favoris';
        $isFavoritedBy = true;
    }

    // Recharger les données du produit avec les counts mis à jour
    $updatedProduit = Produit::with('counts')->find($id);
    $updatedProduit->is_favorited_by = $isFavoritedBy; // Ajouter l'état mis à jour

    return response()->json([
        'message' => $message,
        'produit' => $updatedProduit, // Retourner le produit mis à jour avec is_favorited_by
    ]);
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
            'collaboratif' => 'string',
            'marge_min' => 'nullable|numeric|min:0',
        ]);


        // return response()->json(['message' => 'Produit créé', 'produit' => $request->all()], 201);

        $user = $request->user();
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
            'collaboratif' => $request->collaboratif,
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
    $user = $request->user()->load('commercant');

    if ($produit->commercant_id !== $user->commercant?->id) {
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

    // Récupérer les paramètres du frontend
    $durationDays = $request->input('duration_days', 1); // Par défaut 1 jour
    $targetViews = $request->input('target_views', 100); // Par défaut 100 vues

    // Validation des paramètres
    if ($durationDays < 1 || $durationDays > 20) {
        return response()->json(['message' => 'La durée doit être entre 1 et 20 jours'], 400);
    }
    if (!in_array($targetViews, [100, 500, 1000, 5000, 10000])) {
        return response()->json(['message' => 'Objectif de vues invalide'], 400);
    }

    // Calculer le coût dynamiquement
    $costPerDay = 5; // 5 jetons par jour
    $viewFactors = [100 => 5, 500 => 15, 1000 => 25, 5000 => 50, 10000 => 75]; // Facteur par intervalle
    $coutJetons = ($durationDays * $costPerDay) + $viewFactors[$targetViews];
    $coutJetons = max(5, $coutJetons); // Minimum 5 jetons

    // Vérifier les jetons de l'utilisateur
    if ($user->jetons < $coutJetons) {
        return response()->json(['message' => 'Pas assez de Jetons'], 400);
    }

    // Créer le boost
    $boost = Boost::create([
        'user_id' => $request->user()->id,
        'produit_id' => $id,
        'type' => 'produit',
        'start_date' => now(),
        'end_date' => now()->addDays($durationDays), // Durée personnalisée
        'statut' => 'actif',
        'cout_jetons' => $coutJetons,
        'duration_days' => $durationDays, // Optionnel : stocker si ajouté dans la migration
        'target_views' => $targetViews,   // Optionnel : stocker si ajouté dans la migration
    ]);

    // Déduire les jetons et enregistrer la transaction
    $user->jetons -= $coutJetons;
    $user->save();

    JetonTransaction::create([
        'user_id' => $request->user()->id,
        'type' => 'depense',
        'montant' => -$coutJetons,
        'methode_paiement' => 'Espace_cameroun',
        'statut' => 'validé',
        'date_transaction' => now('Africa/Douala'),
        'nombre_jetons' => $coutJetons, // Corriger pour refléter le coût réel
        'description' => "Dépense de {$coutJetons} Jetons pour booster le produit #{$id} pendant {$durationDays} jours avec un objectif de {$targetViews} vues",
    ]);

    return response()->json([
        'status' => 'success',
        'message' => "Produit boosté pour {$durationDays} jours, {$targetViews} vues au coût de {$coutJetons} Jetons",
        'data' => $boost,
    ]);
}
}