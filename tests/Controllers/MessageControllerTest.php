<?php

declare(strict_types=1);

namespace App\Tests\Controllers;

use Fig\Http\Message\StatusCodeInterface;

class MessageControllerTest extends ControllerTestCase
{
    public function testGetMessagesByGroup(): void
    {
        $hanSolo = $this->addUser('Han Solo');
        $groupId = $this->addGroup('Mos Eisley Cantina', $hanSolo);
        $obiWanKenobi = $this->addUser('Obi-Wan Kenobi');
        $this->addUserToGroup($groupId, $obiWanKenobi);

        $this->addMessage($groupId, $hanSolo, 'What is it, some kind of local trouble?');
        $this->addMessage($groupId, $obiWanKenobi, "Let's just say that we'd like to avoid any Imperial entanglements.");

        $request = $this->requestFactory->createServerRequest('GET', "/groups/$groupId/messages");
        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('groupId', $responseBody);
        $this->assertArrayHasKey('groupMessages', $responseBody);
        $this->assertIsArray($responseBody['groupMessages']);

        $messages = $responseBody['groupMessages'];
        $this->assertCount(2, $messages);

        $messageUserIds = array_column($messages, 'userId');
        $messageUserNames = array_column($messages, 'userName');

        $this->assertContains($hanSolo, $messageUserIds);
        $this->assertContains($obiWanKenobi, $messageUserIds);

        $this->assertContains('Han Solo', $messageUserNames);
        $this->assertContains('Obi-Wan Kenobi', $messageUserNames);
    }

    public function testGetMessagesByGroupOrder(): void
    {
        $userId = $this->addUser('Samuel Clemens');
        $groupId = $this->addGroup('Most Recent First', $userId);

        $msg1 = 'Omitted';
        $msg2 = 'Huckleberry Finn';
        $msg3 = 'Roughing It';
        $msg4 = 'Innocents Abroad';

        $this->addMessage($groupId, $userId, $msg1);
        $this->addMessage($groupId, $userId, $msg2);
        $this->addMessage($groupId, $userId, $msg3);
        $this->addMessage($groupId, $userId, $msg4);

        $request = $this->requestFactory->createServerRequest('GET', "/groups/$groupId/messages?limit=3");
        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $messages = $responseBody['groupMessages'];

        $this->assertCount(3, $messages);

        // Most recent should be first (DESC order)
        $this->assertEquals($msg4, $messages[0]['content']);
        $this->assertEquals($msg3, $messages[1]['content']);
        $this->assertEquals($msg2, $messages[2]['content']);
    }

    public function testGetMessagesByEmptyGroup(): void
    {
        $userId = $this->addUser('Empty Room');
        $groupId = $this->addGroup('No Messages Yet', $userId);

        $request = $this->requestFactory->createServerRequest('GET', "/groups/$groupId/messages");
        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('groupMessages', $responseBody);
        $this->assertIsArray($responseBody['groupMessages']);
        $this->assertEmpty($responseBody['groupMessages']);
    }

    public function testSendMessage(): void
    {
        $ryanId = $this->addUser('Ryan');
        $travelGroupId = $this->addGroup('Travel Ideas', $ryanId);

        $body = json_encode([
            'content' => 'I would like to visit Keukenhof again.',
            'userId' => $ryanId,
        ]);
        $stream = $this->streamFactory->createStream($body);

        $request = $this->requestFactory
            ->createServerRequest('POST', "/groups/$travelGroupId/messages")
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('messageId', $responseBody);
        $this->assertGreaterThanOrEqual(0, $responseBody['messageId']);
    }

    public function testSendMessageNoUser(): void
    {
        $userId = $this->addUser('Not Used');
        $groupId = $this->addGroup('No User Provided', $userId);

        $body = json_encode([
            'content' => '',
            'groupId' => $groupId,
        ]);
        $stream = $this->streamFactory->createStream($body);

        $request = $this->requestFactory
            ->createServerRequest('POST', "/groups/$groupId/messages")
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('error', $responseBody);
        $this->assertEquals('User ID and content are required.', $responseBody['error']);
    }

    public function testSendMessageNoContent(): void
    {
        $userId = $this->addUser('No Comment');
        $groupId = $this->addGroup('Nothing to Say', $userId);

        $body = json_encode([
            'content' => '',
            'groupId' => $groupId,
            'userId' => $userId,
        ]);
        $stream = $this->streamFactory->createStream($body);

        $request = $this->requestFactory
            ->createServerRequest('POST', "/groups/$groupId/messages")
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('error', $responseBody);
        $this->assertEquals('User ID and content are required.', $responseBody['error']);
    }

    public function testSendMessageUserNotInGroup(): void
    {
        $ownerId = $this->addUser('Lando Calrissian');
        $groupId = $this->addGroup('Private Cloud', $ownerId);

        $nonMember = $this->addUser('Darth Vader');

        $body = json_encode([
            'content' => 'Should not be allowed.',
            'userId' => $nonMember,
        ]);
        $stream = $this->streamFactory->createStream($body);

        $request = $this->requestFactory
            ->createServerRequest('POST', "/groups/$groupId/messages")
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);

        $this->assertNotEquals(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
    }
}
