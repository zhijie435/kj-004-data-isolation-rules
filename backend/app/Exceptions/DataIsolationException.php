<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DataIsolationException extends Exception
{
    protected array $context = [];

    protected int $httpStatusCode;

    public function __construct(
        string $message = '数据隔离异常',
        int $code = 0,
        int $httpStatusCode = HttpResponse::HTTP_FORBIDDEN,
        array $context = [],
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->httpStatusCode = $httpStatusCode;
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->getContext(),
        ], $this->getHttpStatusCode());
    }
}
