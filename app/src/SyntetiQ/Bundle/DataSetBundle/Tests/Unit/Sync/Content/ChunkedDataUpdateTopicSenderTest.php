<?php

namespace SyntetiQ\Bundle\DataSetBundle\Tests\Unit\Sync\Content;

use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SyntetiQ\Bundle\DataSetBundle\Sync\Content\ChunkedDataUpdateTopicSender;

class ChunkedDataUpdateTopicSenderTest extends TestCase
{
    private WebsocketClientInterface&MockObject $client;

    private ConnectionChecker&MockObject $connectionChecker;

    private TokenStorageInterface&MockObject $tokenStorage;

    protected function setUp(): void
    {
        $this->client = $this->createMock(WebsocketClientInterface::class);
        $this->connectionChecker = $this->createMock(ConnectionChecker::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
    }

    public function testSendReturnsFalseWhenConnectionIsUnavailable(): void
    {
        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(false);

        $this->client->expects($this->never())
            ->method('publish');

        $sender = new ChunkedDataUpdateTopicSender(
            $this->client,
            $this->connectionChecker,
            $this->tokenStorage
        );

        self::assertFalse($sender->send(['tag-1']));
    }

    public function testSendPublishesSingleChunkWhenPayloadFits(): void
    {
        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(true);

        $this->mockUser('alice');

        $this->client->expects($this->once())
            ->method('publish')
            ->with('oro/data/update', [
                ['username' => 'alice', 'tagname' => 'tag-1'],
                ['username' => 'alice', 'tagname' => 'tag-2'],
            ])
            ->willReturn(true);

        $sender = new ChunkedDataUpdateTopicSender(
            $this->client,
            $this->connectionChecker,
            $this->tokenStorage,
            1024
        );

        self::assertTrue($sender->send(['tag-1', 'tag-2']));
    }

    public function testSendSplitsLargePayloadIntoMultipleChunks(): void
    {
        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(true);

        $this->mockUser('alice');

        $firstTag = str_repeat('a', 50);
        $secondTag = str_repeat('b', 50);
        $thirdTag = str_repeat('c', 50);

        $this->client->expects($this->exactly(2))
            ->method('publish')
            ->withConsecutive(
                [
                    'oro/data/update',
                    [
                        ['username' => 'alice', 'tagname' => $firstTag],
                        ['username' => 'alice', 'tagname' => $secondTag],
                    ],
                ],
                [
                    'oro/data/update',
                    [
                        ['username' => 'alice', 'tagname' => $thirdTag],
                    ],
                ]
            )
            ->willReturn(true);

        $sender = new ChunkedDataUpdateTopicSender(
            $this->client,
            $this->connectionChecker,
            $this->tokenStorage,
            220
        );

        self::assertTrue($sender->send([$firstTag, $secondTag, $thirdTag]));
    }

    private function mockUser(string $identifier): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')
            ->willReturn($identifier);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->method('getToken')
            ->willReturn($token);
    }
}
