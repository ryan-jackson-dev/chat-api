<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    private const MAX_LIMIT = 1000;

    private UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    // Create a new user.
    public function createUser(Request $request, Response $response, array $args): Response
    {
        try {
            $body = $request->getParsedBody() ?: [];

            $name = $body['userName'] ?? '';

            if (empty($name)) {
                $response->getBody()->write(json_encode(['error' => 'User name is required.']));

                return $response
                    ->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST)
                    ->withHeader('Content-Type', 'application/json');
            }
            $userId = $this->repository->create($name);

            $response->getBody()->write(json_encode(['userId' => $userId]));

            return $response
                ->withStatus(StatusCodeInterface::STATUS_CREATED)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            error_log("Error creating user: {$e->getMessage()}");

            $response->getBody()->write(json_encode(['error' => 'An error occurred while creating the user.']));

            return $response
                ->withStatus($e->getCode() ?: StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    public function listUsers(Request $request, Response $response, array $args): Response
    {
        try {
            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int) $params['limit'] : UserController::MAX_LIMIT;
            $offset = isset($params['offset']) ? (int) $params['offset'] : 0;

            if ($offset < 0) {
                $response->getBody()->write(json_encode(['error' => 'Invalid offset.']));

                return $response
                    ->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST)
                    ->withHeader('Content-Type', 'application/json');
            }

            if ($limit < 0 || $limit > UserController::MAX_LIMIT) {
                $response->getBody()->write(json_encode(['error' => 'Invalid limit or maximum exceeded.']));

                return $response
                    ->withStatus(StatusCodeInterface::STATUS_BAD_REQUEST)
                    ->withHeader('Content-Type', 'application/json');
            }
            $users = $this->repository->findAll($limit, $offset);

            $response->getBody()->write(json_encode(['users' => $users]));

            return $response
                ->withStatus(StatusCodeInterface::STATUS_OK)
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            error_log("Error listing users: {$e->getMessage()}");

            $response->getBody()->write(json_encode(['error' => 'An error occurred while listing users.']));

            return $response
                ->withStatus($e->getCode() ?: StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}
