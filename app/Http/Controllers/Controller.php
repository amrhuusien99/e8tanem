<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="اغتنم API Documentation",
 *     description="API documentation for اغتنم application",
 *     @OA\Contact(
 *         email="admin@e8tanem.com",
 *         name="Admin"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 * @OA\Server(
 *     url="/",
 *     description="API Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Schema(
 *     schema="ValidationError",
 *     required={"message", "errors"},
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="The given data was invalid."
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         example={"field": {"The field is required."}}
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="UnauthorizedError",
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Unauthenticated."
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="ForbiddenError",
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Forbidden."
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="NotFoundError",
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Resource not found."
 *     )
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
