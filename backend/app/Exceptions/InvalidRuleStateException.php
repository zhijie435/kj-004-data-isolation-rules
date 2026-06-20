<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvalidRuleStateException extends DataIsolationException
{
    public function __construct(
        string $message = '规则状态无效',
        int $code = 40002,
        array $context = []
    ) {
        parent::__construct($message, $code, HttpResponse::HTTP_BAD_REQUEST, $context);
    }
}
