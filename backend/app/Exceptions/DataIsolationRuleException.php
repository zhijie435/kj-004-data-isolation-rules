<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DataIsolationRuleException extends DataIsolationException
{
    public function __construct(
        string $message = '数据隔离规则异常',
        int $code = 40001,
        array $context = []
    ) {
        parent::__construct($message, $code, HttpResponse::HTTP_BAD_REQUEST, $context);
    }
}
