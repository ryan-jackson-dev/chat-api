<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\SQLConstants;
use App\Repositories\Exceptions\NotFoundException;
use App\Repositories\Exceptions\RepositoryException;
use PDO;
use PDOException;
use Ramsey\Uuid\Uuid;

class GroupRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Create a new group with the user as its first member and owner.
    //
    // This is performed as a single transaction so that a group is only
    // created if the user/group pair satisfies the foreign key constraint.
    public function create(string $name, string $userId): string
    {
        $newId = Uuid::uuid7()->toString();

        $this->pdo->beginTransaction();

        try {
            $insertGroup = $this->pdo->prepare("
               INSERT INTO groups (id, name, created_by) VALUES (:id, :name, :created_by)
            ");
            $insertGroup->execute([
                'id' => $newId,
                'name' => $name,
                'created_by' => $userId,
            ]);


            $insertUser = $this->pdo->prepare("
               INSERT INTO group_memberships (group_id, user_id) VALUES (:group_id, :user_id)
            ");
            $insertUser->execute([
                'group_id' => $newId,
                'user_id' => $userId
            ]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            if ($e->getCode() === SQLConstants::INTEGRITY_CONSTRAINT_VIOLATION) {
                throw new NotFoundException("User not found.", $e);
            }
            throw new RepositoryException("Failed to create group.", cause: $e);
        } finally {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        }
        return $newId;
    }

    public function addUser(string $groupId, string $userId): void
    {
        try {
            $insert = $this->pdo->prepare("
               INSERT INTO group_memberships (group_id, user_id)
               VALUES (:group_id, :user_id)
            ");

            $insert->execute([
                'group_id' => $groupId,
                'user_id' => $userId,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === SQLConstants::INTEGRITY_CONSTRAINT_VIOLATION) {
                // TODO: Disambiguate between a conflict or a missing id.
                throw new NotFoundException("Group or user not found.", $e);
            }
            throw new RepositoryException("Failed to add user to group.", cause: $e);
        }
    }

    public function findByGroup(string $groupId, int $limit, int $offset): array
    {
        try {
            $select = $this->pdo->prepare("
                SELECT users.id AS userId, users.name AS userName
                FROM users JOIN group_memberships ON users.id = group_memberships.user_id
                WHERE group_memberships.group_id = :group_id
                ORDER BY users.created_at, users.id
                LIMIT :limit
                OFFSET :offset
            ");
            $select->execute(['group_id' => $groupId, 'limit' => $limit, 'offset' => $offset]);

            return $select->fetchAll();
        } catch (PDOException $e) {
            throw new RepositoryException("Failed to find users for group.", cause: $e);
        }
    }

    public function findAll(int $limit, int $offset): array
    {
        try {
            $select = $this->pdo->prepare("
                SELECT
                    groups.id AS groupId,
                    groups.name AS groupName,
                    users.id AS groupOwnerId,
                    users.name AS groupOwnerName
                FROM groups JOIN users ON groups.created_by = users.id
                ORDER BY groups.created_at, groups.id
                LIMIT :limit
                OFFSET :offset
            ");
            $select->execute(['limit' => $limit, 'offset' => $offset]);

            return $select->fetchAll();
        } catch (PDOException $e) {
            throw new RepositoryException("Failed to find groups.", cause: $e);
        }
    }
}
