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
    url: "http://192.168.1.153:8000",
    description: "Serveur local de développement"
)]
class SwaggerInfoController extends Controller
{
}