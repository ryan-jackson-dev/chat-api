<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\SQLConstants;
use App\Repositories\Exceptions\ConflictException;
use App\Repositories\Exceptions\RepositoryException;
use PDO;
use PDOException;
use Ramsey\Uuid\Uuid;

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Create a new user and return their ID as UUID4.
    public function create(string $name): string
    {
        $newId = Uuid::uuid4()->toString();

        try {
            $insert = $this->pdo->prepare("INSERT INTO users (id, name) VALUES (:id, :name)");

            $insert->execute([
                'id' => $newId,
                'name' => $name,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === SQLConstants::INTEGRITY_CONSTRAINT_VIOLATION) {
                throw new ConflictException("User already exists.", $e);
            }
            throw new RepositoryException("Failed to create user.", cause: $e);
        }

        return $newId;
    }

    public function findAll(int $limit, int $offset): array
    {
        try {
            $select = $this->pdo->prepare("
                SELECT id AS userId, name AS userName, created_at AS createdAt
                FROM users
                ORDER BY created_at, id
                LIMIT :limit
                OFFSET :offset
            ");
            $select->execute(['limit' => $limit, 'offset' => $offset]);

            return $select->fetchAll();
        } catch (PDOException $e) {
            throw new RepositoryException("Failed to find users.", cause: $e);
        }
    }
}
