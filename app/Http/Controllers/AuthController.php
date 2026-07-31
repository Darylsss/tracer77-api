<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use App\Models\Family;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    #[OA\Post(
        path: "/api/register",
        summary: "Inscription d'un nouvel utilisateur",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nom", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "nom", type: "string", example: "Daryl"),
                    new OA\Property(property: "email", type: "string", example: "daryl@example.com"),
                    new OA\Property(property: "password", type: "string", example: "123456"),
                    new OA\Property(property: "password_confirmation", type: "string", example: "123456"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Inscription réussie"),
            new OA\Response(response: 422, description: "Erreur de validation"),
        ]
    )]
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom'      => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'nom'      => $request->nom,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        

        $token = $user->createToken('tracer77')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

   #[OA\Post(
        path: "/api/login",
        summary: "Connexion d'un utilisateur",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", example: "daryl@example.com"),
                    new OA\Property(property: "password", type: "string", example: "123456"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Connexion réussie"),
            new OA\Response(response: 401, description: "Email ou mot de passe incorrect"),
        ]
    )]

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email ou mot de passe incorrect.',
            ], 401);
        }

        $token = $user->createToken('tracer77')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    #[OA\Post(
        path: "/api/logout",
        summary: "Déconnexion de l'utilisateur",
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(response: 200, description: "Déconnexion réussie"),
        ]
    )]


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnecté.',
        ]);
    }

    // Récupérer l'user connecté
#[OA\Get(
    path: "/api/user",
    summary: "Récupérer l'utilisateur connecté",
    security: [["sanctum" => []]],
    responses: [
        new OA\Response(response: 200, description: "Utilisateur retourné"),
        new OA\Response(response: 401, description: "Non authentifié"),
    ]
)]

public function me(Request $request)
{
    $user = $request->user();
    $data = $user->toArray();
    $data['role'] = $user->getRoleNames()->first();
    return response()->json($data);
}

// Modifier le nom
#[OA\Put(
    path: "/api/user/update-name",
    summary: "Modifier le nom",
    security: [["sanctum" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["nom"],
            properties: [
                new OA\Property(property: "nom", type: "string", example: "Daryl"),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Nom mis à jour"),
        new OA\Response(response: 422, description: "Erreur de validation"),
    ]
)]

public function updateName(Request $request)
{
    $validator = Validator::make($request->all(), [
        'nom' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 422);
    }

    $request->user()->update(['nom' => $request->nom]);

    return response()->json([
        'success' => true,
        'message' => 'Nom mis à jour.',
        'user' => $request->user(),
    ]);
}

// Modifier le mot de passe
#[OA\Put(
    path: "/api/user/update-password",
    summary: "Modifier le mot de passe",
    security: [["sanctum" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["current_password", "new_password", "new_password_confirmation"],
            properties: [
                new OA\Property(property: "current_password", type: "string", example: "123456"),
                new OA\Property(property: "new_password", type: "string", example: "nouveau123"),
                new OA\Property(property: "new_password_confirmation", type: "string", example: "nouveau123"),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Mot de passe mis à jour"),
        new OA\Response(response: 401, description: "Mot de passe actuel incorrect"),
    ]
)]

public function updatePassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'current_password' => 'required|string',
        'new_password'     => 'required|string|min:6|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 422);
    }

    if (!Hash::check($request->current_password, $request->user()->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Mot de passe actuel incorrect.',
        ], 401);
    }

    $request->user()->update([
        'password' => Hash::make($request->new_password),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Mot de passe mis à jour.',
    ]);
}

// Supprimer le compte
#[OA\Delete(
    path: "/api/user/delete",
    summary: "Supprimer le compte",
    security: [["sanctum" => []]],
    responses: [
        new OA\Response(response: 200, description: "Compte supprimé"),
    ]
)]

public function deleteAccount(Request $request)
{
    $request->user()->currentAccessToken()->delete();
    $request->user()->delete();

    return response()->json([
        'success' => true,
        'message' => 'Compte supprimé.',
    ]);
}

#[OA\Post(
    path: "/api/forgot-password",
    summary: "Demander un code de réinitialisation",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email"],
            properties: [new OA\Property(property: "email", type: "string", example: "daryl@example.com")]
        )
    ),
    responses: [new OA\Response(response: 200, description: "Code envoyé si l'email existe")]
)]
public function forgotPassword(Request $request)
{
    $validator = Validator::make($request->all(), ['email' => 'required|email']);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    $user = User::where('email', $request->email)->first();

    if ($user) {
        $code = (string) random_int(100000, 999999); // code à 6 chiffres

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($code), 'created_at' => now()]
        );

        Mail::raw(
            "Vous avez demandé à réinitialiser votre mot de passe sur Tracer77.\n\n"
            . "Voici votre code : {$code}\n\n"
            . "Ce code est valable 15 minutes. Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.",
            function ($message) use ($request) {
                $message->to($request->email)->subject('Code de réinitialisation - Tracer77');
            }
        );
    }

    return response()->json([
        'success' => true,
        'message' => 'Si cet email existe, un code a été envoyé.',
    ]);
}

#[OA\Post(
    path: "/api/reset-password",
    summary: "Réinitialiser le mot de passe avec le code reçu",
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email", "token", "password", "password_confirmation"],
            properties: [
                new OA\Property(property: "email", type: "string", example: "daryl@example.com"),
                new OA\Property(property: "token", type: "string", example: "le_code_recu_par_email"),
                new OA\Property(property: "password", type: "string", example: "nouveaupass123"),
                new OA\Property(property: "password_confirmation", type: "string", example: "nouveaupass123"),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Mot de passe réinitialisé"),
        new OA\Response(response: 422, description: "Code invalide ou expiré"),
    ]
)]
public function resetPassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'token' => 'required',
        'password' => 'required|string|min:6|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

    if (!$record || !Hash::check($request->token, $record->token)) {
        return response()->json(['success' => false, 'message' => 'Code invalide.'], 422);
    }

    if (now()->diffInMinutes($record->created_at) > 15) {
        return response()->json(['success' => false, 'message' => 'Code expiré.'], 422);
    }

    $user = User::where('email', $request->email)->first();
    $user->update(['password' => Hash::make($request->password)]);

    DB::table('password_reset_tokens')->where('email', $request->email)->delete();

    return response()->json(['success' => true, 'message' => 'Mot de passe réinitialisé.']);
}
}