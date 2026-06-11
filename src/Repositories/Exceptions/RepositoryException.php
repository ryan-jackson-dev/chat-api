<?php

declare(strict_types=1);

namespace App\Repositories\Exceptions;

use Exception;
use Fig\Http\Message\StatusCodeInterface;

class RepositoryException extends Exception
{
    public function __construct(
        string $message,
        int $statusCode = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR,
        ?Exception $cause = null
    ) {
        parent::__construct($message, $statusCode, $cause);
    }
}
