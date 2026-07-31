<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Position;
use App\Models\Enfant;

class PositionController extends Controller
{
    // Endpoint appelé par le boîtier toutes les 30 secondes
    public function recevoir(Request $request)
    {
        // Valider les données reçues du boîtier
        $request->validate([
    'device_id'  => 'required|string',
    'latitude'   => 'required|numeric',
    'longitude'  => 'required|numeric',
    'vitesse'    => 'required|numeric',
    'direction'  => 'required|numeric',
    'satellites' => 'required|integer',
    'batterie'   => 'required|numeric',
    'sos'        => 'required|integer',
]);

$position = Position::create([
    'device_id'  => $request->device_id,
    'lat'        => $request->latitude,
    'lng'        => $request->longitude,
    'vitesse'    => $request->vitesse,
    'direction'  => $request->direction,
    'satellites' => $request->satellites,
    'batterie'   => $request->batterie,
    'sos'        => $request->sos,
]);

        // Si SOS déclenché → alerter immédiatement
        if ($request->sos == 1) {
            // D4ryl implémente ici la notification push Firebase
            // et l'alerte communautaire
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Position reçue',
            'data'    => $position
        ], 201);
    }

    // Historique des 24 dernières heures
    public function historique()
    {
        $positions = Position::where('created_at', '>=', now()->subHours(24))
                             ->orderBy('created_at', 'desc')
                             ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $positions
        ]);
    }

    // Dernière position connue
    public function derniere()
    {
        $position = Position::latest()->first();

        return response()->json([
            'status' => 'success',
            'data'   => $position
        ]);
    }
}
