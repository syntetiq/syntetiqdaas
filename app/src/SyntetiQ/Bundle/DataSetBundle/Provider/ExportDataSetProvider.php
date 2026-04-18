<?php

namespace SyntetiQ\Bundle\DataSetBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\ExportDataSetTopic;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetExport;

class ExportDataSetProvider
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private MessageProducerInterface $producer,
        private TokenStorageInterface $securityTokenStorage
    ) {}

    public function sendMessage(DataSet $dataSet, array $exportTags = []): void
    {
        $user = $this->securityTokenStorage->getToken()->getUser();
        $exportTags = $this->normalizeTags($exportTags);

        $dataSetExport = new DataSetExport();
        $dataSetExport->setDataSetId($dataSet->getId());
        $dataSetExport->setOwner($user);
        $dataSetExport->setCreatedAt(new \DateTime());
        $em = $this->doctrine->getManagerForClass(DataSetExport::class);
        $em->persist($dataSetExport);
        $em->flush();

        $this->producer->send(
            ExportDataSetTopic::getName(),
            [
                'dataSetExportId' => $dataSetExport->getId(),
                'exportTags' => $exportTags,
            ]
        );
    }

    private function normalizeTags(array $exportTags): array
    {
        $normalizedTags = [];
        foreach ($exportTags as $tag) {
            $tag = trim((string) $tag);
            if ($tag === '' || in_array($tag, $normalizedTags, true)) {
                continue;
            }

            $normalizedTags[] = $tag;
        }

        return $normalizedTags;
    }
}
