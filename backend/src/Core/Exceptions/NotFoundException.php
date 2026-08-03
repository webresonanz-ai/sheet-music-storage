<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Endpoint not found')
    {
        parent::__construct(404, $message);
    }
}