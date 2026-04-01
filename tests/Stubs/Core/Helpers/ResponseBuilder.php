<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stub matching {@see ResponseBuilder} constructor signature for controllers under test.
 */
final class ResponseBuilder
{
    private mixed $data = null;

    private ?string $error = null;

    public function __construct(private readonly Request $request) {}

    public function setData(mixed $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function setError(string $error): self
    {
        $this->error = $error;

        return $this;
    }

    public function json(): JsonResponse
    {
        if ($this->error !== null) {
            return response()->json(['error' => $this->error], 200);
        }

        return response()->json(['data' => $this->data], 200);
    }
}
