<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\Enfant;
use App\Models\Alerte;
use App\Models\ZoneEtat;
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

        if (($request->sos ?? 0) == 1) {
            Alerte::create([
                'family_id' => $enfant->family_id,
                'enfant_id' => $enfant->id,
                'type' => 'sos',
                'message' => ($enfant->prenom ?: 'Un enfant') . ' a déclenché une alerte SOS.',
            ]);
        }

        $this->verifierZones($enfant, $position);

        return response()->json(['success' => true, 'position' => $position], 201);
    }

    /**
     * Vérifie chaque zone à alerte pour cet enfant, avec délai de grâce.
     * L'état (hors_zone_depuis / alerte_envoyee) est mémorisé par couple
     * enfant/zone dans la table zone_etats.
     */
    private function verifierZones(Enfant $enfant, Position $positionActuelle): void
    {
        $zones = $enfant->places()->where('alerte_sortie', true)->get();

        foreach ($zones as $zone) {
            $rayon = $zone->rayon ?? 150;
            $delaiGraceMinutes = $zone->delai_grace_minutes ?? 15;

            $estDedans = $this->estDansLeRayon(
                $positionActuelle->lat,
                $positionActuelle->lng,
                $zone->latitude,
                $zone->longitude,
                $rayon
            );

            $etat = ZoneEtat::firstOrCreate(
                ['enfant_id' => $enfant->id, 'place_id' => $zone->id],
                ['hors_zone_depuis' => null, 'alerte_envoyee' => false]
            );

            if ($estDedans) {
                // Retour dans la zone : si une alerte de sortie avait été envoyée, on notifie le retour.
                if ($etat->alerte_envoyee) {
                    Alerte::create([
                        'family_id' => $enfant->family_id,
                        'enfant_id' => $enfant->id,
                        'type' => 'entree_zone',
                        'message' => ($enfant->prenom ?: 'Un enfant') . " est de retour dans la zone « {$zone->nom} ».",
                    ]);
                }
                $etat->update(['hors_zone_depuis' => null, 'alerte_envoyee' => false]);
                continue;
            }

            // L'enfant est hors zone.
            if ($etat->hors_zone_depuis === null) {
                // Première position détectée hors zone : on démarre le délai de grâce, sans alerter tout de suite.
                $etat->update(['hors_zone_depuis' => now()]);
                continue;
            }

            if (!$etat->alerte_envoyee && now()->diffInMinutes($etat->hors_zone_depuis) >= $delaiGraceMinutes) {
                Alerte::create([
                    'family_id' => $enfant->family_id,
                    'enfant_id' => $enfant->id,
                    'type' => 'sortie_zone',
                    'message' => ($enfant->prenom ?: 'Un enfant') . " est sorti(e) de la zone « {$zone->nom} » depuis plus de {$delaiGraceMinutes} minutes.",
                ]);
                $etat->update(['alerte_envoyee' => true]);
            }
        }
    }

    /**
     * Calcule si un point est à l'intérieur d'un rayon donné (formule de Haversine).
     */
    private function estDansLeRayon(float $lat1, float $lng1, float $lat2, float $lng2, float $rayonMetres): bool
    {
        $rayonTerre = 6371000; // mètres

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $rayonTerre * $c;

        return $distance <= $rayonMetres;
    }
}