<?php

namespace App\Http\Controllers;

use App\Models\Enfant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class EnfantController extends Controller
{
    #[OA\Post(
        path: "/api/enfants",
        summary: "Ajouter un enfant à tracker",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nom", "prenom", "identifiant_boitier"],
                properties: [
                    new OA\Property(property: "nom", type: "string", example: "Doe"),
                    new OA\Property(property: "prenom", type: "string", example: "Timmy"),
                    new OA\Property(property: "identifiant_boitier", type: "string", example: "TRC-0042"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Enfant ajouté"),
            new OA\Response(response: 422, description: "Erreur de validation"),
        ]
    )]
    public function store(Request $request)
{
    $user = $request->user();

    if (!$user->family_id) {
        return response()->json(['success' => false, 'message' => 'Vous n\'appartenez à aucune famille.'], 404);
    }

    $validator = Validator::make($request->all(), [
        'nom' => 'nullable|string|max:255',
        'prenom' => 'required|string|max:255',
        'identifiant_boitier' => 'required|string|unique:enfants,identifiant_boitier',
        'photo' => 'nullable|image|max:5120', // 5 Mo max
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    $photoPath = null;
    if ($request->hasFile('photo')) {
        $photoPath = $request->file('photo')->store('enfants', 'public');
    }

    $enfant = Enfant::create([
        'user_id' => $user->id,
        'family_id' => $user->family_id,
        'nom' => $request->nom ?? '',
        'prenom' => $request->prenom,
        'photo' => $photoPath,
        'identifiant_boitier' => $request->identifiant_boitier,
    ]);

    return response()->json(['success' => true, 'enfant' => $enfant], 201);
}
}