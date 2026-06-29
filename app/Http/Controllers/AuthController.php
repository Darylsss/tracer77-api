<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

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
}