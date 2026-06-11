<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Exceptions\BadRequestException;
use App\Repositories\Exceptions\RepositoryException;
use PDO;
use PDOException;

class MessageRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(string $groupId, string $userId, string $content): int
    {
        try {
            $insert = $this->pdo->prepare("
               INSERT INTO messages (group_id, user_id, content)
               SELECT :group_id, :user_id, :content
               FROM group_memberships
               WHERE group_id = :group_id AND user_id = :user_id
               RETURNING id
            ");

            $insert->execute([
                'group_id' => $groupId,
                'user_id' => $userId,
                'content' => $content,
            ]);

            $newId = $insert->fetchColumn();

            if (!$newId) {
                // TODO: This could be caused by other things such as content length. Improve.
                throw new BadRequestException("User does not belong to group.");
            }

            return (int) $newId;
        } catch (PDOException $e) {
            throw new RepositoryException("Failed to create message.", cause: $e);
        }
    }

    // In the future we may want to specify the sort order.
    public function findByGroup(string $groupId, int $limit, int $offset): array
    {
        try {
            // TODO: This allows for pagination drift which I don't like due to
            //       DESC, but am going to live with for now.
            $select = $this->pdo->prepare("
                SELECT
                    messages.id AS messageId,
                    users.id AS userId,
                    users.name AS userName,
                    messages.content AS content,
                    messages.created_at AS createdAt
                FROM messages JOIN users ON messages.user_id = users.id
                WHERE group_id = :group_id
                ORDER BY messages.created_at DESC, messages.id DESC
                LIMIT :limit
                OFFSET :offset
            ");
            $select->execute(['group_id' => $groupId, 'limit' => $limit, 'offset' => $offset]);

            return $select->fetchAll();
        } catch (PDOException $e) {
            throw new RepositoryException("Failed to find messages for group.", cause: $e);
        }
    }
}
