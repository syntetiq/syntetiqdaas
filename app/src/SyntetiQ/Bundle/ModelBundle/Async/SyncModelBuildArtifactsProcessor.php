<?php

namespace SyntetiQ\Bundle\ModelBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use SyntetiQ\Bundle\ModelBundle\Async\Topic\SyncModelBuildArtifactsTopic;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;
use SyntetiQ\Bundle\ModelBundle\Service\ModelBuildArtifactSyncer;

class SyncModelBuildArtifactsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private JobRunner $jobRunner,
        private ModelBuildArtifactSyncer $modelBuildArtifactSyncer
    ) {}

    public static function getSubscribedTopics(): array
    {
        return [SyncModelBuildArtifactsTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $data = $message->getBody();
        $buildId = (int) ($data['buildId'] ?? 0);
        if ($buildId <= 0) {
            return self::REJECT;
        }

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner, Job $job) use ($buildId) {
                $modelBuild = $this->doctrine->getRepository(ModelBuild::class)->find($buildId);
                if (!$modelBuild instanceof ModelBuild) {
                    return false;
                }

                $this->modelBuildArtifactSyncer->sync($modelBuild);

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }
}
