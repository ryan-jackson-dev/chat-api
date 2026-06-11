<?php

declare(strict_types=1);

namespace App\Tests\Controllers;

use Fig\Http\Message\StatusCodeInterface;

class GroupControllerTest extends ControllerTestCase
{
    public function testStatusRoute(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/status');
        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('status', $responseBody);
        $this->assertEquals('Chat API is running.', $responseBody['status']);
        $this->assertArrayHasKey('version', $responseBody);
        $this->assertNotEmpty($responseBody['version']);
    }

    public function testGetAllGroups(): void
    {
        $djDoboy = $this->addUser('DJ Doboy');
        $djTiesto = $this->addUser('DJ Tiesto');

        $trancequility = $this->addGroup('Trancequility', $djDoboy);
        $inSearchOfSunrise = $this->addGroup('In Search of Sunrise', $djTiesto);
        $eurojams = $this->addGroup('Eurojams', $djDoboy);

        $request = $this->requestFactory->createServerRequest('GET', '/groups?offset=1');
        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('groups', $responseBody);

        $groups = $responseBody['groups'];
        $this->assertIsArray($groups);
        $this->assertEquals(2, count($groups));

        $groupIds = array_column($groups, 'groupId');
        $groupOwnerIds = array_column($groups, 'groupOwnerId');
        $groupOwnerNames = array_column($groups, 'groupOwnerName');

        $this->assertNotContains($trancequility, $groupIds);
        $this->assertContains($inSearchOfSunrise, $groupIds);
        $this->assertContains($eurojams, $groupIds);
        $this->assertContains($djDoboy, $groupOwnerIds);
        $this->assertContains($djTiesto, $groupOwnerIds);
        $this->assertContains('DJ Doboy', $groupOwnerNames);
        $this->assertContains('DJ Tiesto', $groupOwnerNames);
    }

    public function testCreateGroup(): void
    {
        $name = 'Vocal Trance';
        $body = json_encode(['groupName' => $name, 'userId' => $this->addUser('CJ Stone')]);
        $stream = $this->streamFactory->createStream($body);

        $request = $this->requestFactory
            ->createServerRequest('POST', '/groups')
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('groupId', $responseBody);
        $this->assertEquals(36, strlen($responseBody['groupId']));
        $this->assertArrayHasKey('groupName', $responseBody);
        $this->assertEquals($name, $responseBody['groupName']);
    }

    public function testCreateGroupNoName(): void
    {
        $body = json_encode([]);
        $stream = $this->streamFactory->createStream($body);

        $request = $this->requestFactory
            ->createServerRequest('POST', '/groups')
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertEquals('Group name and User ID are required.', $responseBody['error']);
    }

    public function testCreateDuplicateGroup(): void
    {
        $userId = $this->addUser('David Gilmour');
        $groupName = 'Pink Floyd';

        $this->addGroup($groupName, $userId);

        $body = json_encode(['groupName' => $groupName, 'userId' => $userId]);

        $stream = $this->streamFactory->createStream($body);

        $request = $this->requestFactory
            ->createServerRequest('POST', '/groups')
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        // TODO: This should really be CONFLICT.
        $this->assertEquals(StatusCodeInterface::STATUS_NOT_FOUND, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        if ($responseBody !== null) {
            $this->assertArrayHasKey('error', $responseBody);
        }
    }

    public function testGetUsersByGroup(): void
    {
        $cjStone = $this->addUser('CJ Stone');
        $groupId = $this->addGroup('Vocal Trance', $cjStone);

        $djTiesto = $this->addUser('DJ Tiesto');
        $this->addUserToGroup($groupId, $djTiesto);

        $request = $this->requestFactory->createServerRequest('GET', "/groups/{$groupId}/users");
        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('groupId', $responseBody);
        $this->assertArrayHasKey('groupUsers', $responseBody);

        $this->assertEquals($groupId, $responseBody['groupId']);
        $this->assertIsArray($responseBody['groupUsers']);

        $users = $responseBody['groupUsers'];

        $this->assertCount(2, $users);

        $userIds = array_column($users, 'userId');

        $this->assertContains($cjStone, $userIds);
        $this->assertContains($djTiesto, $userIds);
    }

    public function testJoinGroup(): void
    {
        $djDoboy = $this->addUser('DJ Doboy');
        $groupId = $this->addGroup('The Vocal Edition', $djDoboy);

        $djPiccolo = $this->addUser('DJ Piccolo');

        $body = json_encode(['userId' => $djPiccolo]);
        $stream = $this->streamFactory->createStream($body);

        $request = $this->requestFactory
            ->createServerRequest('POST', "/groups/{$groupId}/users")
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('groupId', $responseBody);
        $this->assertArrayHasKey('userId', $responseBody);
        $this->assertArrayHasKey('joined', $responseBody);
        $this->assertEquals($groupId, $responseBody['groupId']);
        $this->assertEquals($djPiccolo, $responseBody['userId']);
        $this->assertTrue($responseBody['joined']);
    }

    public function testJoinGroupNoUser(): void
    {
        $userId = $this->addUser('Kate Bush');
        $groupId = $this->addGroup('Running up that hill', $userId);

        $body = json_encode([]);
        $stream = $this->streamFactory->createStream($body);

        $request = $this->requestFactory
            ->createServerRequest('POST', "/groups/{$groupId}/users")
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertEquals($responseBody['error'], 'Group ID and User ID are required.');
    }

    public function testJoinGroupTwice(): void
    {
        $userId = $this->addUser('Nick Rhodes');
        $groupId = $this->addGroup('Duran Duran', $userId);

        $body = json_encode(['userId' => $userId]);

        $stream = $this->streamFactory->createStream($body);

        $request = $this->requestFactory
            ->createServerRequest('POST', "/groups/{$groupId}/users")
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        // TODO: This should really be CONFLICT.
        $this->assertEquals(StatusCodeInterface::STATUS_NOT_FOUND, $response->getStatusCode());
    }
}
