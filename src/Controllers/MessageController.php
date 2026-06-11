<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\MessageRepository;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MessageController
{
    private const MAX_LIMIT = 1000;

    private MessageRepository $repository;

    public function __construct(MessageRepository $repository)
    {
        $this->repository = $repository;
    }

    // Get messages by group. Returns an array of messages with their ID, content and other metadata.
    //
    // PERFORMANCE: See the limit and offset items.
    //    SECURITY: We might not want a user to see all messages in a group.
    public function listGroupMessages(Request $request, Response $response, array $args): Response
    {
        try {
            $groupId = $args['groupId'];

            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int) $params['limit'] : MessageController::MAX_LIMIT;
            $offset = isset($params['offset']) ? (int) $params['offset'] : 0;

            if ($offset < 0) {
                $response->getBody()->write(json_encode(['error' => 'Invalid offset.']));

                return $response
                    ->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST)
                    ->withHeader('Content-Type', 'application/json');
            }

            if ($limit < 0 || $limit > MessageController::MAX_LIMIT) {
                $response->getBody()->write(json_encode(['error' => 'Invalid limit or maximum exceeded.']));

                return $response
                    ->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST)
                    ->withHeader('Content-Type', 'application/json');
            }
            $messages = $this->repository->findByGroup($groupId, $limit, $offset);

            $response->getBody()->write(json_encode([
                'groupId' => $groupId,
                'groupMessages' => $messages
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            error_log("Error listing group messages: {$e->getMessage()}");

            $response->getBody()->write(json_encode(['error' => 'An error occurred while listing the group messages.']));

            return $response
                ->withStatus($e->getCode() ?: StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    // Send a message to a group.
    //
    // In the future one might want to send a message between users.
    public function sendMessage(Request $request, Response $response, array $args): Response
    {
        try {
            $body = $request->getParsedBody() ?: [];
            $groupId = $args['groupId'];
            $userId = $body['userId'] ?? '';
            $content = $body['content'] ?? '';

            if (empty($userId) || empty($content)) {
                $response->getBody()->write(json_encode(['error' => 'User ID and content are required.']));

                return $response
                    ->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST)
                    ->withHeader('Content-Type', 'application/json');
            }
            $messageId = $this->repository->create($groupId, $userId, $content);

            $response->getBody()->write(json_encode(['messageId' => $messageId]));

            return $response
                ->withStatus(StatusCodeInterface::STATUS_CREATED)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            error_log("Error sending message: {$e->getMessage()}");

            $response->getBody()->write(json_encode(['error' => 'An error occurred while sending the message.']));

            return $response
                ->withStatus($e->getCode() ?: StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}
