<?php

declare(strict_types=1);

namespace App\Database;

// See:
// - https://sqlite.org/rescode.html
// - https://learn.microsoft.com/en-us/sql/odbc/reference/appendixes/appendix-a-odbc-error-codes
final class SQLConstants
{
    public const INTEGRITY_CONSTRAINT_VIOLATION = '23000';
}
