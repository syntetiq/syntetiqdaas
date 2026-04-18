<?php

namespace SyntetiQ\Bundle\DataSetBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\DataSetQaTopic;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaPaths;
use SyntetiQ\Bundle\ModelBundle\Model\DatasetQaStatus;
use SyntetiQ\Bundle\ModelBundle\Service\DatasetQaStorageManager;

class DataSetQaProvider
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private MessageProducerInterface $producer,
        private DatasetQaStorageManager $datasetQaStorageManager
    ) {}

    public function sendMessage(DataSet $dataSet): void
    {
        $em = $this->doctrine->getManagerForClass(DataSet::class);
        $dataSet
            ->setDatasetQaStatus(DatasetQaStatus::QUEUED)
            ->setDatasetQaStartedAt(null)
            ->setDatasetQaFinishedAt(null)
            ->setDatasetQaHeartbeatAt(null)
            ->setDatasetQaProgress(0.0)
            ->setDatasetQaProgressMessage('Queued for dataset QA execution.')
            ->setDatasetQaErrorOutput(null);

        $em->persist($dataSet);
        $em->flush();

        try {
            $this->datasetQaStorageManager->clearPrefix(DatasetQaPaths::getDataSetBaseStorageDir($dataSet));
        } catch (\Throwable) {
        }

        $this->producer->send(
            DataSetQaTopic::getName(),
            [
                'dataSetId' => (int) $dataSet->getId(),
            ]
        );
    }
}
