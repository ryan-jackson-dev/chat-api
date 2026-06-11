<?php

declare(strict_types=1);

namespace App\Tests\Controllers;

use App\Database\ConnectionFactory;
use App\Repositories\GroupRepository;
use App\Repositories\MessageRepository;
use App\Repositories\UserRepository;
use DI\Container;
use PDO;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

abstract class ControllerTestCase extends TestCase
{
    protected App $app;
    protected ServerRequestFactory $requestFactory;
    protected StreamFactory $streamFactory;

    protected function setUp(): void
    {
        $this->app = (require __DIR__ . '/../../src/app.php')();
        $this->requestFactory = new ServerRequestFactory();
        $this->streamFactory = new StreamFactory();

        $this->setUpPDO();
    }

    protected function setUpPDO(): void
    {
        $testDb = ConnectionFactory::create('sqlite::memory:');

        $schema = file_get_contents(__DIR__ . '/../../database/schema.sql');
        $testDb->exec($schema);

        /** @var Container $container */
        $container = $this->app->getContainer();
        $container->set(PDO::class, $testDb);
    }

    protected function addGroup(string $name, string $userId): string
    {
        $repository = $this->app->getContainer()->get(GroupRepository::class);
        return $repository->create($name, $userId);
    }

    protected function addUser(string $name): string
    {
        $repository = $this->app->getContainer()->get(UserRepository::class);
        return $repository->create($name);
    }

    protected function addUserToGroup(string $groupId, string $userId): void
    {
        $repository = $this->app->getContainer()->get(GroupRepository::class);
        $repository->addUser($groupId, $userId);
    }

    protected function addMessage(string $groupId, string $userId, string $content): int
    {
        $repository = $this->app->getContainer()->get(MessageRepository::class);
        return $repository->create($groupId, $userId, $content);
    }
}
