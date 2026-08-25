<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use RuntimeException;

trait HandlesUpstreamErrors
{
    protected function safeJson(callable $callback): JsonResponse
    {
        try {
            return response()->json($callback());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }
}
