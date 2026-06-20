<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response as HttpResponse;

class UnauthorizedDataAccessException extends DataIsolationException
{
    public function __construct(
        string $message = '无权访问该数据',
        int $code = 40301,
        array $context = []
    ) {
        parent::__construct($message, $code, HttpResponse::HTTP_FORBIDDEN, $context);
    }
}
