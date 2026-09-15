<?php

namespace App\Http\Controllers;

use App\Models\Enfant;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DeviceController extends Controller
{
    #[OA\Get(
        path: "/api/devices/phone",
        summary: "Récupérer le numéro du parent responsable pour un boîtier donné",
        parameters: [
            new OA\Parameter(name: "identifiant_boitier", in: "query", required: true, schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Numéro retourné"),
            new OA\Response(response: 404, description: "Boîtier ou numéro introuvable"),
        ]
    )]
    public function phone(Request $request)
    {
        $enfant = Enfant::where('identifiant_boitier', $request->identifiant_boitier)->first();

        if (!$enfant || !$enfant->family) {
            return response()->json(['success' => false, 'message' => 'Boîtier inconnu.'], 404);
        }

        $telephone = $enfant->family->creator->telephone ?? null;

        if (!$telephone) {
            return response()->json(['success' => false, 'message' => 'Aucun numéro configuré.'], 404);
        }

        return response()->json(['success' => true, 'telephone' => $telephone]);
    }
}