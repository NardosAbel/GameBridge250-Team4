<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    /**
     * Return standardized success JSON response.
     *
     * @param  array<string, mixed>  $meta
     */
    protected function successResponse(
        mixed $data = [],
        string $message = 'Success.',
        int $code = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    /**
     * Return standardized error JSON response.
     *
     * @param  array<string, mixed>  $errors
     */
    protected function errorResponse(
        string $message = 'An error occurred.',
        array $errors = [],
        int $code = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
