<?php

namespace App\Http\Controllers;

use App\Models\Alerte;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AlertController extends Controller
{
    #[OA\Get(
        path: "/api/alerts",
        summary: "Lister les alertes de la famille de l'utilisateur connecté",
        security: [["sanctum" => []]],
        responses: [new OA\Response(response: 200, description: "Liste retournée")]
    )]
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->family_id) {
            return response()->json(['success' => true, 'alertes' => []]);
        }

        $alertes = Alerte::with('enfant')
            ->where('family_id', $user->family_id)
            ->latest()
            ->limit(30)
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'type' => $a->type,
                    'message' => $a->message,
                    'enfant' => $a->enfant?->prenom,
                    'created_at' => $a->created_at->toIso8601String(),
                ];
            });

        return response()->json(['success' => true, 'alertes' => $alertes]);
    }
}