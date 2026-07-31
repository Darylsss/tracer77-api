<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FamilyInvite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use App\Models\Family;


class FamilyController extends Controller
{  

#[OA\Post(
    path: "/api/family/create",
    summary: "Créer un espace famille",
    security: [["sanctum" => []]],
    requestBody: new OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "nom", type: "string", example: "Famille Dupont"),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Famille créée"),
        new OA\Response(response: 409, description: "Appartient déjà à une famille"),
        new OA\Response(response: 422, description: "Erreur de validation"),
    ]
)]

public function create(Request $request)
{
    $user = $request->user();

    // Sécurité : empêche de créer une 2e famille si déjà dans une
    if ($user->family_id !== null) {
        return response()->json([
            'success' => false,
            'message' => 'Vous appartenez déjà à une famille.',
        ], 409);
    }

    $validator = Validator::make($request->all(), [
        'nom' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    $family = Family::create([
        'nom' => $request->nom ?? $user->nom . ' - Famille',
        'created_by' => $user->id,
    ]);

    $user->update(['family_id' => $family->id]);
    $user->assignRole('admin_famille');

    return response()->json([
        'success' => true,
        'message' => 'Famille créée.',
        'family' => $family,
    ]);
}


     #[OA\Post(
    path: "/api/family/invite",
    summary: "Inviter un membre dans l'espace famille",
    security: [["sanctum" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email"],
            properties: [
                new OA\Property(property: "email", type: "string", example: "membre-test@exemple.com"),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Invitation envoyée"),
        new OA\Response(response: 403, description: "Non autorisé"),
    ]
)]
    
    public function invite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        // Sécurité : seul un admin_famille peut inviter
        if (!$user->hasRole('admin_famille')) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $invite = FamilyInvite::create([
            'family_id' => $user->family_id,
            'email' => $request->email,
            'token' => Str::random(40), // token aléatoire de 40 caractères
            'expires_at' => now()->addHours(48), // valable 48h
        ]);

        // Envoi de l'email (on configurera Mailtrap juste après)
        Mail::raw(
    "Vous êtes invité(e) à rejoindre une famille sur Tracer77 !\n\n"
    . "1. Téléchargez l'application Tracer77\n"
    . "2. Créez votre compte (ou connectez-vous si vous en avez déjà un)\n"
    . "3. Choisissez « Rejoindre une famille »\n"
    . "4. Collez ce code d'invitation : {$invite->token}\n\n"
    . "Ce code est valable 48h.",
    function ($message) use ($request) {
        $message->to($request->email)->subject('Invitation à rejoindre une famille - Tracer77');
    }
);

        return response()->json(['success' => true, 'message' => 'Invitation envoyée.']);
    
}

#[OA\Post(
    path: "/api/family/accept-invite",
    summary: "Accepter une invitation à rejoindre une famille",
    security: [["sanctum" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["token"],
            properties: [
                new OA\Property(property: "token", type: "string", example: "le_token_recu_par_email"),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Invitation acceptée"),
        new OA\Response(response: 404, description: "Invitation invalide"),
        new OA\Response(response: 410, description: "Invitation expirée"),
    ]
)]

public function acceptInvite(Request $request)
    {
        $user = $request->user();

    if ($user->family_id !== null) {
        return response()->json([
            'success' => false,
            'message' => 'Vous appartenez déjà à une famille.',
        ], 409);
    }

        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $invite = FamilyInvite::where('token', $request->token)
                               ->whereNull('accepted_at')
                               ->first();

        if (!$invite) {
            return response()->json(['success' => false, 'message' => 'Invitation invalide ou déjà utilisée.'], 404);
        }

        if ($invite->expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'Invitation expirée.'], 410);
        }

        $user = $request->user(); 

if ($user->email !== $invite->email) {
    return response()->json([
        'success' => false,
        'message' => 'Cette invitation ne correspond pas à votre compte.',
    ], 403);
}

        $user->update(['family_id' => $invite->family_id]);
        $user->assignRole('membre');

        $invite->update(['accepted_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Vous avez rejoint la famille.']);
    }

 #[OA\Get(
    path: "/api/family/members",
    summary: "Lister les membres et enfants de la famille",
    security: [["sanctum" => []]],
    responses: [new OA\Response(response: 200, description: "Liste retournée")]
)]
public function members(Request $request)
{
    $user = $request->user();

    if (!$user->family_id) {
        return response()->json(['success' => false, 'message' => 'Vous n\'appartenez à aucune famille.'], 404);
    }

    $users = $user->family->users()->with('lastPosition')->get()->map(function ($u) {
        return [
            'id' => $u->id,
            'nom' => $u->nom,
            'role' => $u->getRoleNames()->first(),
            'partage_position' => $u->partage_position,
            'position' => $u->partage_position ? $u->lastPosition : null,
        ];
    });

    $enfants = $user->family->enfants()->with('lastPosition')->get()->map(function ($e) {
    return [
        'id' => $e->id,
        'nom' => $e->nom,
        'prenom' => $e->prenom,
        'photo' => $e->photo ? asset('storage/' . $e->photo) : null,
        'position' => $e->lastPosition,
    ];
});

    return response()->json([
        'success' => true,
        'membres' => $users,
        'enfants' => $enfants,
    ]);
}

#[OA\Delete(
    path: "/api/family/members/{id}",
    summary: "Retirer un membre de la famille",
    security: [["sanctum" => []]],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Membre retiré"),
        new OA\Response(response: 403, description: "Non autorisé"),
        new OA\Response(response: 404, description: "Membre introuvable"),
    ]
)]
public function removeMember(Request $request, $id)
{
    $admin = $request->user();

    if (!$admin->hasRole('admin_famille')) {
        return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
    }

    $membre = \App\Models\User::where('id', $id)
                               ->where('family_id', $admin->family_id)
                               ->first();

    if (!$membre) {
        return response()->json(['success' => false, 'message' => 'Membre introuvable.'], 404);
    }

    if ($membre->id === $admin->id) {
        return response()->json(['success' => false, 'message' => 'Vous ne pouvez pas vous retirer vous-même.'], 400);
    }

    $membre->update(['family_id' => null]);
    $membre->removeRole('membre');

    return response()->json(['success' => true, 'message' => 'Membre retiré de la famille.']);
}

#[OA\Post(
    path: "/api/user/toggle-position-sharing",
    summary: "Activer/désactiver le partage de sa position",
    security: [["sanctum" => []]],
    responses: [
        new OA\Response(response: 200, description: "Préférence mise à jour"),
    ]
)]

public function togglePositionSharing(Request $request)
{
    $user = $request->user();
    $user->update(['partage_position' => !$user->partage_position]);

    return response()->json([
        'success' => true,
        'partage_position' => $user->partage_position,
    ]);
}
}
