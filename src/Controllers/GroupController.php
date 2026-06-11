<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\GroupRepository;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GroupController
{
    private const MAX_LIMIT = 1000;

    private GroupRepository $repository;

    public function __construct(GroupRepository $repository)
    {
        $this->repository = $repository;
    }

    // Create a new group.
    //
    // Expects a JSON body with a "name" field. Returns the ID and name of the new group.
    //
    // A duplicate group name will result in an error response.
    //
    // In the future we may want to allow additional metadata when creating a group, such as a description.
    //
    // SECURITY: A user might not be allowed to create a group. An approval might also be required.
    public function createGroup(Request $request, Response $response, array $args): Response
    {
        try {
            $body = $request->getParsedBody() ?: [];
            $groupName = $body['groupName'] ?? '';
            $userId = $body['userId'] ?? '';

            if (empty($groupName) || empty($userId)) {
                $response->getBody()->write(json_encode(['error' => 'Group name and User ID are required.']));

                return $response
                    ->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST)
                    ->withHeader('Content-Type', 'application/json');
            }

            // It is possible one might want to sanitize the group name to eliminate special characters,
            // but we are going to assume valid input for now.
            $groupId = $this->repository->create($groupName, $userId);

            $response->getBody()->write(json_encode([
                'groupId' => $groupId,
                'groupName' => $groupName,
            ]));

            return $response
                ->withStatus(StatusCodeInterface::STATUS_CREATED)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            error_log("Error creating group: {$e->getMessage()}");

            $response->getBody()->write(json_encode(['error' => 'An error occurred while creating the group.']));

            return $response
                ->withStatus($e->getCode() ?: StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    // Get all groups. Returns an array of groups with their ID, name and other metadata.
    //
    // PERFORMANCE: See the limit and offset items.
    //    SECURITY: A user might only have access to certain grops.
    public function listGroups(Request $request, Response $response, array $args): Response
    {
        try {
            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int) $params['limit'] : GroupController::MAX_LIMIT;
            $offset = isset($params['offset']) ? (int) $params['offset'] : 0;

            if ($offset < 0) {
                $response->getBody()->write(json_encode(['error' => 'Invalid offset.']));

                return $response
                    ->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST)
                    ->withHeader('Content-Type', 'application/json');
            }

            if ($limit < 0 || $limit > GroupController::MAX_LIMIT) {
                $response->getBody()->write(json_encode(['error' => 'Invalid limit or maximum exceeded.']));

                return $response
                    ->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST)
                    ->withHeader('Content-Type', 'application/json');
            }

            $groups = $this->repository->findAll($limit, $offset);

            $response->getBody()->write(json_encode(['groups' => $groups]));

            return $response
                ->withStatus(StatusCodeInterface::STATUS_OK)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            error_log("Error listing groups: {$e->getMessage()}");

            $response->getBody()->write(json_encode(['error' => 'An error occurred while listing groups.']));

            return $response
                ->withStatus($e->getCode() ?: StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    // Get users by group. Returns an array of users with their ID, name and other metadata.
    //
    // PERFORMANCE: See the limit and offset items.
    //    SECURITY: We might not want a user to see all other users in a group.
    public function listGroupUsers(Request $request, Response $response, array $args): Response
    {
        try {
            $groupId = $args['groupId'] ?? '';

            if (empty($groupId)) {
                $response->getBody()->write(json_encode(['error' => 'Group ID is required.']));

                return $response
                    ->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST)
                    ->withHeader('Content-Type', 'application/json');
            }

            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int) $params['limit'] : GroupController::MAX_LIMIT;
            $offset = isset($params['offset']) ? (int) $params['offset'] : 0;

            if ($offset < 0) {
                $response->getBody()->write(json_encode(['error' => 'Invalid offset.']));

                return $response
                    ->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST)
                    ->withHeader('Content-Type', 'application/json');
            }

            if ($limit < 0 || $limit > GroupController::MAX_LIMIT) {
                $response->getBody()->write(json_encode(['error' => 'Invalid limit or maximum exceeded.']));

                return $response
                    ->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST)
                    ->withHeader('Content-Type', 'application/json');
            }

            $users = $this->repository->findByGroup($groupId, $limit, $offset);

            $response->getBody()->write(json_encode([
                'groupId' => $groupId,
                'groupUsers' => $users,
            ]));

            return $response
                ->withStatus(StatusCodeInterface::STATUS_OK)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            error_log("Error listing group users: {$e->getMessage()}");

            $response->getBody()->write(json_encode(['error' => 'An error occurred while listing group users.']));

            return $response
                ->withStatus($e->getCode() ?: StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    // Join a group. Expects a JSON body with a "userId" field. Returns the group ID and user ID.
    //
    // SECURITY: A user might not be allowed to join a group or may first require an approval.
    public function joinGroup(Request $request, Response $response, array $args): Response
    {
        try {
            $groupId = $args['groupId'] ?? '';
            $body = $request->getParsedBody() ?: [];
            $userId = $body['userId'] ?? '';

            if (empty($groupId) || empty($userId)) {
                $response->getBody()->write(json_encode(['error' => 'Group ID and User ID are required.']));

                return $response
                    ->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST)
                    ->withHeader('Content-Type', 'application/json');
            }
            $this->repository->addUser($groupId, $userId);

            $response->getBody()->write(json_encode([
                'groupId' => $groupId,
                'userId' => $userId,
                // This is not really used, but in the future one might want
                // to communicate a pending or other type of approval status.
                'joined' => true,
            ]));

            return $response
                ->withStatus(StatusCodeInterface::STATUS_OK)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            error_log("Error joining group: {$e->getMessage()}");

            $response->getBody()->write(json_encode(['error' => 'An error occurred while joining the group.']));

            return $response
                ->withStatus($e->getCode() ?: StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}
