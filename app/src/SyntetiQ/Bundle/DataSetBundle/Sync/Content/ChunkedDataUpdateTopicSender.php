<?php

namespace SyntetiQ\Bundle\DataSetBundle\Sync\Content;

use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\SyncBundle\Content\DataUpdateTopicSender;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ChunkedDataUpdateTopicSender extends DataUpdateTopicSender
{
    private const DATA_UPDATE_TOPIC = 'oro/data/update';
    private const MAX_CHUNK_BYTES = 60000;

    public function __construct(
        private WebsocketClientInterface $client,
        private ConnectionChecker $connectionChecker,
        private TokenStorageInterface $tokenStorage,
        private int $maxChunkBytes = self::MAX_CHUNK_BYTES
    ) {
        parent::__construct($client, $connectionChecker, $tokenStorage);
    }

    public function send(array $tags): bool
    {
        if (empty($tags) || !$this->connectionChecker->checkConnection()) {
            return false;
        }

        $userName = $this->getUserName();
        $payload = array_map(
            static fn (string $tag): array => ['username' => $userName, 'tagname' => $tag],
            $tags
        );

        foreach ($this->chunkPayload($payload) as $chunk) {
            if (!$this->client->publish(self::DATA_UPDATE_TOPIC, $chunk)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Split large websocket data-update notifications into smaller frames.
     * This avoids the vendor payload generator failing on oversized frames.
     *
     * @param array<int, array{username: ?string, tagname: string}> $payload
     *
     * @return array<int, array<int, array{username: ?string, tagname: string}>>
     */
    private function chunkPayload(array $payload): array
    {
        $chunks = [];
        $currentChunk = [];
        $currentChunkBytes = 2; // JSON array brackets: []

        foreach ($payload as $item) {
            $itemJson = json_encode($item);
            if ($itemJson === false) {
                $currentChunk[] = $item;
                continue;
            }

            $additionalBytes = strlen($itemJson) + ($currentChunk === [] ? 0 : 1);
            if ($currentChunk !== [] && $currentChunkBytes + $additionalBytes > $this->maxChunkBytes) {
                $chunks[] = $currentChunk;
                $currentChunk = [];
                $currentChunkBytes = 2;
                $additionalBytes = strlen($itemJson);
            }

            $currentChunk[] = $item;
            $currentChunkBytes += $additionalBytes;
        }

        if ($currentChunk !== []) {
            $chunks[] = $currentChunk;
        }

        return $chunks === [] ? [$payload] : $chunks;
    }

    private function getUserName(): ?string
    {
        $userName = null;
        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                $userName = $user->getUserIdentifier();
            }
        }

        return $userName;
    }
}
