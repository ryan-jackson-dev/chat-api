<?php

declare(strict_types=1);

namespace App\Tests\Controllers;

use Fig\Http\Message\StatusCodeInterface;

class UserControllerTest extends ControllerTestCase
{
    public function testCreateUser(): void
    {
        $body = json_encode(['userName' => 'Mark Twain']);
        $stream = $this->streamFactory->createStream($body);

        $request = $this->requestFactory
            ->createServerRequest('POST', '/users')
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('userId', $responseBody);
        $this->assertEquals(36, strlen($responseBody['userId']));
    }

    public function testCreateDuplicateUser(): void
    {
        $body = json_encode(['userName' => 'Mark Twain']);
        $stream = $this->streamFactory->createStream($body);

        $request = $this->requestFactory
            ->createServerRequest('POST', '/users')
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('userId', $responseBody);
        $this->assertEquals(36, strlen($responseBody['userId']));

        $body = json_encode(['userName' => 'Mark Twain']);
        $stream = $this->streamFactory->createStream($body);

        $request = $this->requestFactory
            ->createServerRequest('POST', '/users')
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_CONFLICT, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testListUsers(): void
    {
        $djDoboy = $this->addUser('DJ Doboy');
        $cjStone = $this->addUser('CJ Stone');

        $body = json_encode(['limit' => 500]);
        $stream = $this->streamFactory->createStream($body);

        $request = $this->requestFactory
            ->createServerRequest('GET', '/users')
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('users', $responseBody);
        $this->assertCount(2, $responseBody['users']);

        $userIds = array_column($responseBody['users'], 'userId');
        $userNames = array_column($responseBody['users'], 'userName');

        $this->assertContains($djDoboy, $userIds);
        $this->assertContains($cjStone, $userIds);

        $this->assertContains('DJ Doboy', $userNames);
        $this->assertContains('CJ Stone', $userNames);
    }

    public function testListNoUsers(): void
    {
        $body = json_encode(['limit' => 500]);
        $stream = $this->streamFactory->createStream($body);

        $request = $this->requestFactory
            ->createServerRequest('GET', '/users')
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('users', $responseBody);
        $this->assertEmpty($responseBody['users']);
    }

    public function testListUsersPaginated(): void
    {
        $djDoboy = $this->addUser('DJ Doboy');
        $cjStone = $this->addUser('CJ Stone');
        $hanSolo = $this->addUser('Han Solo');
        $markTwain = $this->addUser('Mark Twain');

        // Grab the first two
        $request = $this->requestFactory
            ->createServerRequest('GET', '/users?limit=2')
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('users', $responseBody);
        $this->assertCount(2, $responseBody['users']);

        $userIdsFirstHalf = array_column($responseBody['users'], 'userId');

        // Grab the next (last) two
        $request = $this->requestFactory
            ->createServerRequest('GET', '/users?limit=2&offset=2')
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('users', $responseBody);
        $this->assertCount(2, $responseBody['users']);

        $userIdsSecondHalf = array_column($responseBody['users'], 'userId');

        $expectedIds = [$djDoboy, $cjStone, $hanSolo, $markTwain];

        // Since these are all (potentially) added within the same millisecond,
        // sort them by id because that is what the fetch does.
        sort($expectedIds);

        $this->assertContains($expectedIds[0], $userIdsFirstHalf);
        $this->assertContains($expectedIds[1], $userIdsFirstHalf);

        $this->assertContains($expectedIds[2], $userIdsSecondHalf);
        $this->assertContains($expectedIds[3], $userIdsSecondHalf);
    }
}
