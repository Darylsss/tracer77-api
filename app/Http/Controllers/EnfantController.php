<?php

namespace App\Http\Controllers;

use App\Models\Enfant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    if (!$user->can('gerer_espace')) {
        return response()->json(['success' => false, 'message' => 'Non autorisé. Seul l\'admin peut ajouter un enfant.'], 403);
    }

    $validator = Validator::make($request->all(), [
        'nom' => 'nullable|string|max:255',
        'prenom' => 'required|string|max:255',
        'identifiant_boitier' => 'required|string|unique:enfants,identifiant_boitier',
        'photo' => 'nullable|image|max:5120',
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

    #[OA\Put(
        path: "/api/enfants/{enfant}",
        summary: "Modifier un enfant (prénom / photo) — admin uniquement",
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "enfant", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Enfant mis à jour"),
            new OA\Response(response: 403, description: "Non autorisé"),
            new OA\Response(response: 404, description: "Enfant introuvable"),
            new OA\Response(response: 422, description: "Erreur de validation"),
        ]
    )]
    public function update(Request $request, Enfant $enfant)
    {
        $user = $request->user();

        if ($enfant->family_id !== $user->family_id) {
            return response()->json(['success' => false, 'message' => 'Enfant introuvable.'], 404);
        }

        if (!$user->can('gerer_espace')) {
            return response()->json(['success' => false, 'message' => 'Non autorisé. Seul l\'admin peut modifier un enfant.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'prenom' => 'sometimes|string|max:255',
            'nom' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if ($request->hasFile('photo')) {
            if ($enfant->photo) {
                Storage::disk('public')->delete($enfant->photo);
            }
            $enfant->photo = $request->file('photo')->store('enfants', 'public');
        }

        $enfant->fill($request->only(['prenom', 'nom']));
        $enfant->save();

        return response()->json([
            'success' => true,
            'message' => 'Enfant mis à jour.',
            'enfant' => [
                'id' => $enfant->id,
                'nom' => $enfant->nom,
                'prenom' => $enfant->prenom,
                'photo' => $enfant->photo ? asset('storage/' . $enfant->photo) : null,
            ],
        ]);
    }

    #[OA\Delete(
        path: "/api/enfants/{enfant}",
        summary: "Retirer un enfant suivi — admin uniquement",
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "enfant", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Enfant retiré"),
            new OA\Response(response: 403, description: "Non autorisé"),
            new OA\Response(response: 404, description: "Enfant introuvable"),
        ]
    )]
    public function destroy(Request $request, Enfant $enfant)
    {
        $user = $request->user();

        if ($enfant->family_id !== $user->family_id) {
            return response()->json(['success' => false, 'message' => 'Enfant introuvable.'], 404);
        }

        if (!$user->can('gerer_espace')) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        if ($enfant->photo) {
            Storage::disk('public')->delete($enfant->photo);
        }

        // Nettoyage explicite : évite des lieux/positions orphelins si tes
        // migrations n'ont pas de contrainte onDelete('cascade').
        $enfant->places()->delete();
        $enfant->positions()->delete();
        $enfant->delete();

        return response()->json(['success' => true, 'message' => 'Enfant retiré.']);
    }
}