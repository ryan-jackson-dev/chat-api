<?php

declare(strict_types=1);

use App\Controllers\GroupController;
use App\Controllers\MessageController;
use App\Controllers\UserController;
use App\Database\ConnectionFactory;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/constants.php';

return function (): App {
    $container = new Container();

    // Configure PDO for SQLite using the var folder.
    //
    // PERFORMANCE: Since SQLite just uses a local file handle, I am not going to
    //              concern myself with persistent connections or a shared pool.
    $container->set(PDO::class, function () {
        $dbPath = __DIR__ . '/../var/database.sqlite';
        return ConnectionFactory::create("sqlite:{$dbPath}");
    });

    AppFactory::setContainer($container);

    /** @var App<Psr\Container\ContainerInterface> $app */
    $app = AppFactory::create();

    // See also: https://www.slimframework.com/docs/v4/middleware/body-parsing.html
    $app->addBodyParsingMiddleware();

    // We would want to disable details in production, but this is good for now.
    // See also: https://www.slimframework.com/docs/v4/middleware/error-handling.html
    $app->addErrorMiddleware(true, true, true);

    // Status endpoint to confirm the service is running.
    $app->get('/status', function (Request $request, Response $response): Response {
        $response->getBody()->write(json_encode([
            'status' => 'Chat API is running.',
            'version' => APP_VERSION
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Serve a simple HTML page to test the API.
    $app->get('/', function (Request $request, Response $response): Response {
        $htmlPath = __DIR__ . '/test-console.html';
        $html = file_get_contents($htmlPath);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    });

    $app->group('/users', function (RouteCollectorProxy $usersRoot) {
        $usersRoot->get('', [UserController::class, 'listUsers']);
        $usersRoot->post('', [UserController::class, 'createUser']);
    });

    $app->group('/groups', function (RouteCollectorProxy $groupsRoot) {
        $groupsRoot->get('', [GroupController::class, 'listGroups']);
        $groupsRoot->post('', [GroupController::class, 'createGroup']);

        $groupsRoot->group('/{groupId}', function (RouteCollectorProxy $groupById) {
            $groupById->get('/users', [GroupController::class, 'listGroupUsers']);
            $groupById->post('/users', [GroupController::class, 'joinGroup']);

            $groupById->get('/messages', [MessageController::class, 'listGroupMessages']);
            $groupById->post('/messages', [MessageController::class, 'sendMessage']);
        });
    });
    return $app;
};
