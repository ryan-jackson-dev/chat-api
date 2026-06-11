<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

class ConnectionFactory
{
    public static function create(string $dsn, array $options = []): PDO
    {
        $pdo = new PDO($dsn, null, null, $options);

        // Set this to ensure that foreign keys are enforced for every connection.
        $pdo->exec('PRAGMA foreign_keys = ON;');

        // Enable Write-Ahead Logging to minimize contention.
        $pdo->exec('PRAGMA journal_mode=WAL;');

        // Use FETCH_ASSOC to avoid duplicate results by numeric index.
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // This is the default as of 8.0.0, but set it here to be explicit.
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // I believe the default is 60. Five seems reasonable in light of WAL.
        $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5);

        return $pdo;
    }
}
