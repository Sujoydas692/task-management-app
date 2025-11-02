<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    protected function success(mixed $data = null, string $message = '', int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => $message
        ], $code);
    }

    protected function error(string|array $messages = ['Internal Server Error'], int $code = 500): JsonResponse
    {
        if (is_string($messages)) {
            $messages = [$messages];
        }

        return response()->json([
            'status' => false,
            'messages' => $messages
        ], $code);
    }
}
