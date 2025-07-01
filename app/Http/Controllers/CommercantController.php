<?php
// app/Http/Controllers/CommercantController.php
namespace App\Http\Controllers;

use App\Models\Commercant;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class CommercantController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'ville' => 'required|string|max:255',
        ]);

        $user = auth()->user();

        if ($user->commercant) {
            return response()->json(['message' => 'Vous avez déjà un compte commerçant'], 400);
        }

        $commercant = new Commercant([
            'id' => Uuid::uuid4()->toString(),
            'user_id' => $user->id,
            'nom' => $request->nom,
            'description' => $request->description,
            'logo' => $request->logo,
            'ville' => $request->ville,
            'actif' => true,
        ]);
        $commercant->save();

        return response()->json(['message' => 'Compte commerçant créé', 'commercant' => $commercant], 201);
    }

    public function index()
    {
        $commercants = Commercant::with('user', 'boutiques')->get();
        return response()->json(['commercants' => $commercants]);
    }
}