<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Tracer77 API",
    version: "1.0.0",
    description: "API d'authentification et de géolocalisation pour Tracer77"
)]
#[OA\Server(
    url: "http://192.168.100.7:8000/",
    description: "Serveur local de développement"
)]

#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Entrez votre token Sanctum ici"
)]
class SwaggerInfoController extends Controller
{
}

#[OA\Schema(
    schema: 'Place',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'enfant_id', type: 'integer', example: 4),
        new OA\Property(property: 'created_by', type: 'integer', example: 3),
        new OA\Property(property: 'type', type: 'string', enum: ['domicile', 'ecole', 'proche', 'autre']),
        new OA\Property(property: 'nom', type: 'string', example: 'Chez Mamie'),
        new OA\Property(property: 'latitude', type: 'number', format: 'float'),
        new OA\Property(property: 'longitude', type: 'number', format: 'float'),
        new OA\Property(property: 'rayon', type: 'integer'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class PlaceSchema {}