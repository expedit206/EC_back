<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Panier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommandeController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.produit_id' => 'required|exists:products,id',
            'items.*.quantite' => 'required|integer|min:1',
            'items.*.prix' => 'required|numeric|min:0',
        ]);

        $total = collect($data['items'])->sum(fn($item) => $item['prix'] * $item['quantite']);
        $order = Order::create([
            'id' => \Str::uuid(),
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => $total,
        ]);

        foreach ($data['items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'produit_id' => $item['produit_id'],
                'quantite' => $item['quantite'],
                'prix' => $item['prix'],
            ]);
        }

        // Vider le panier
        Panier::where('user_id', $user->id)->delete();

        return response()->json(['message' => 'Commande passée avec succès', 'order' => $order]);
    }

    public function index()
    {
        $user = Auth::user();
        $orders = Order::where('user_id', $user->id)
            ->with('items.produit')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                    'total' => $order->total,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'produit_id' => $item->produit_id,
                            'quantite' => $item->quantite,
                            'prix' => $item->prix,
                            'produit' => [
                                'nom' => $item->produit->nom,
                                'photo_url' => $item->produit->photo_url,
                            ],
                        ];
                    }),
                ];
            });

        return response()->json($orders);
    }
}