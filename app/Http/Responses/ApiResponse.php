<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

class ApiResponse
{
    /**
     * Return a standardized success response.
     *
     * Format: { "success": true, "data": [...], "meta": {...} }
     */
    public static function success(mixed $data = null, ?string $message = null, int $status = 200, array $meta = []): JsonResponse
    {
        $payload = [
            'success' => true,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data instanceof ResourceCollection && $data->resource instanceof AbstractPaginator) {
            $paginated = $data->response()->getData(true);
            $payload['data'] = $paginated['data'];
            $payload['meta'] = array_merge($paginated['meta'] ?? [], $meta);
            if (isset($paginated['links'])) {
                $payload['links'] = $paginated['links'];
            }
        } elseif ($data instanceof AbstractPaginator) {
            $payload['data'] = $data->items();
            $payload['meta'] = array_merge([
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ], $meta);
        } elseif ($data instanceof JsonResource) {
            $payload['data'] = $data->resolve();
            if (! empty($meta)) {
                $payload['meta'] = $meta;
            }
        } else {
            $payload['data'] = $data;
            if (! empty($meta)) {
                $payload['meta'] = $meta;
            }
        }

        return response()->json($payload, $status);
    }

    /**
     * Return a standardized error response.
     *
     * Format: { "success": false, "message": "...", "errors": {...} }
     */
    public static function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
