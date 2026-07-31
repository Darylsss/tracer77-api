<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\Enfant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class PositionController extends Controller
{
    #[OA\Post(
        path: "/api/positions",
        summary: "Envoyer sa position (membre connecté)",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["lat", "lng"],
                properties: [
                    new OA\Property(property: "lat", type: "number", example: 6.3654),
                    new OA\Property(property: "lng", type: "number", example: 2.4183),
                    new OA\Property(property: "vitesse", type: "number", example: 0),
                    new OA\Property(property: "direction", type: "number", example: 0),
                    new OA\Property(property: "satellites", type: "integer", example: 0),
                    new OA\Property(property: "batterie", type: "number", example: 85),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: "Position enregistrée")]
    )]
    public function storeForUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'vitesse' => 'nullable|numeric',
            'direction' => 'nullable|numeric',
            'satellites' => 'nullable|integer',
            'batterie' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        $position = $user->positions()->create([
            'lat' => $request->lat,
            'lng' => $request->lng,
            'vitesse' => $request->vitesse ?? 0,
            'direction' => $request->direction ?? 0,
            'satellites' => $request->satellites ?? 0,
            'batterie' => $request->batterie ?? 0,
            'sos' => 0,
        ]);

        return response()->json(['success' => true, 'position' => $position], 201);
    }

    #[OA\Post(
        path: "/api/devices/positions",
        summary: "Envoyer une position depuis un boîtier ESP32",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["identifiant_boitier", "lat", "lng"],
                properties: [
                    new OA\Property(property: "identifiant_boitier", type: "string", example: "TRC-0042"),
                    new OA\Property(property: "lat", type: "number", example: 6.3654),
                    new OA\Property(property: "lng", type: "number", example: 2.4183),
                    new OA\Property(property: "vitesse", type: "number", example: 0),
                    new OA\Property(property: "direction", type: "number", example: 0),
                    new OA\Property(property: "satellites", type: "integer", example: 4),
                    new OA\Property(property: "batterie", type: "number", example: 72),
                    new OA\Property(property: "sos", type: "integer", example: 0),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Position enregistrée"),
            new OA\Response(response: 404, description: "Boîtier inconnu"),
        ]
    )]
    public function storeForDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifiant_boitier' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $enfant = Enfant::where('identifiant_boitier', $request->identifiant_boitier)->first();

        if (!$enfant) {
            return response()->json(['success' => false, 'message' => 'Boîtier inconnu.'], 404);
        }

        $position = $enfant->positions()->create([
            'lat' => $request->lat,
            'lng' => $request->lng,
            'vitesse' => $request->vitesse ?? 0,
            'direction' => $request->direction ?? 0,
            'satellites' => $request->satellites ?? 0,
            'batterie' => $request->batterie ?? 0,
            'sos' => $request->sos ?? 0,
        ]);

        return response()->json(['success' => true, 'position' => $position], 201);
    }
}