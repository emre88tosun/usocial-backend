<?php

namespace App\Swagger;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="uSocial API",
 *         version="1.0.0",
 *         description="API documentation for uSocial"
 *     ),
 *     @OA\Components(
 *         @OA\SecurityScheme(
 *             securityScheme="bearerAuth",
 *             type="http",
 *             scheme="bearer",
 *             bearerFormat="JWT"
 *         ),
 *     )
 * )
 */
class ApiDocumentation
{
    // No methods are needed here, just the annotations.
}
