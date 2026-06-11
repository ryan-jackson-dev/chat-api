<?php

declare(strict_types=1);

namespace App\Repositories\Exceptions;

use Exception;
use Fig\Http\Message\StatusCodeInterface;

class ConflictException extends RepositoryException
{
    public function __construct(
        string $message,
        ?Exception $cause = null
    ) {
        parent::__construct($message, StatusCodeInterface::STATUS_CONFLICT, $cause);
    }
}
