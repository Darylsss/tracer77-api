<?php
// app/Http/Controllers/PlaceController.php

namespace App\Http\Controllers;

use App\Models\Enfant;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class PlaceController extends Controller
{
    #[OA\Get(
        path: "/api/enfants/{enfant}/places",
        summary: "Lister les lieux d'un enfant",
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "enfant", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Liste des lieux"),
            new OA\Response(response: 403, description: "Non autorisé"),
        ]
    )]
    public function index(Request $request, Enfant $enfant)
    {
        $this->authorize('viewAny', [Place::class, $enfant]);

        return response()->json([
            'success' => true,
            'places' => $enfant->places()->latest()->get(),
        ]);
    }

    #[OA\Post(
        path: "/api/enfants/{enfant}/places",
        summary: "Ajouter un lieu pour un enfant (admin uniquement)",
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "enfant", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["type", "nom", "latitude", "longitude"],
                properties: [
                    new OA\Property(property: "type", type: "string", enum: ["domicile", "ecole", "proche", "autre"], example: "domicile"),
                    new OA\Property(property: "nom", type: "string", example: "Maison familiale"),
                    new OA\Property(property: "latitude", type: "number", format: "float", example: 6.4315),
                    new OA\Property(property: "longitude", type: "number", format: "float", example: 2.3624),
                    new OA\Property(property: "rayon", type: "integer", example: 150),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Lieu créé"),
            new OA\Response(response: 403, description: "Non autorisé"),
            new OA\Response(response: 422, description: "Erreur de validation"),
        ]
    )]
    public function store(Request $request, Enfant $enfant)
    {
        $this->authorize('create', [Place::class, $enfant]);

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:domicile,ecole,proche,autre',
            'nom' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'rayon' => 'nullable|integer|min:10|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $place = $enfant->places()->create([
            ...$validator->validated(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'place' => $place], 201);
    }

    #[OA\Put(
        path: "/api/places/{place}",
        summary: "Modifier un lieu (admin uniquement)",
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "place", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nom", type: "string", example: "Chez Mamie"),
                    new OA\Property(property: "latitude", type: "number", format: "float"),
                    new OA\Property(property: "longitude", type: "number", format: "float"),
                    new OA\Property(property: "rayon", type: "integer"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Lieu mis à jour"),
            new OA\Response(response: 403, description: "Non autorisé"),
            new OA\Response(response: 404, description: "Lieu introuvable"),
        ]
    )]
    public function update(Request $request, Place $place)
    {
        $this->authorize('update', $place);

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:100',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'rayon' => 'sometimes|integer|min:10|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $place->update($validator->validated());

        return response()->json(['success' => true, 'place' => $place]);
    }

    #[OA\Delete(
        path: "/api/places/{place}",
        summary: "Supprimer un lieu (admin uniquement)",
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "place", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Lieu supprimé"),
            new OA\Response(response: 403, description: "Non autorisé"),
            new OA\Response(response: 404, description: "Lieu introuvable"),
        ]
    )]
    public function destroy(Request $request, Place $place)
    {
        $this->authorize('delete', $place);

        $place->delete();

        return response()->json(['success' => true, 'message' => 'Lieu supprimé.']);
    }
}