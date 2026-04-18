<?php

namespace SyntetiQ\Bundle\ModelBundle\Async;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\Filesystem\Filesystem;
use SyntetiQ\Bundle\ModelBundle\Async\Topic\CleanModelBuildFilesTopic;
use SyntetiQ\Bundle\ModelBundle\Model\ModelBuildConstants;
use SyntetiQ\Bundle\ModelBundle\Service\DatasetQaStorageManager;

class CleanModelBuildFilesProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    public function __construct(
        private DatasetQaStorageManager $datasetQaStorageManager
    ) {}

    public static function getSubscribedTopics(): array
    {
        return [CleanModelBuildFilesTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $data = $message->getBody();
        $modelId = (int) $data['modelId'];
        $buildId = (int) $data['buildId'];

        $fs = new Filesystem();

        $buildDir = sprintf('%s/%d_%d/', ModelBuildConstants::getBuildRootDir(), $modelId, $buildId);
        if ($fs->exists($buildDir)) {
            $fs->remove($buildDir);
        }

        $tbDir = sprintf('%s/%d_%d', ModelBuildConstants::getTensorBoardBaseDir(), $modelId, $buildId);
        if ($fs->exists($tbDir)) {
            $fs->remove($tbDir);
        }

        $buildStoragePrefix = sprintf('builds/%d_%d', $modelId, $buildId);
        $this->datasetQaStorageManager->clearPrefix($buildStoragePrefix . '/dataset_qa');
        $this->datasetQaStorageManager->clearPrefix($buildStoragePrefix . '/logs');

        return self::ACK;
    }
}
