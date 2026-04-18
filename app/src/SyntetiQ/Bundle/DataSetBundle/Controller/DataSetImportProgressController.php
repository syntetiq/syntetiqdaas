<?php

namespace SyntetiQ\Bundle\DataSetBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job as JobEntity;
use Oro\Component\MessageQueue\Job\Job;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\ImportDataSetTopic;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;

class DataSetImportProgressController extends AbstractController
{
    private const JOB_NAME_PREFIX = ImportDataSetTopic::class;

    public function __construct(
        private ManagerRegistry $doctrine
    ) {}

    #[Route(
        path: '/{id}/progress',
        name: 'syntetiq_data_set_import_progress',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
        options: ['expose' => true],
    )]
    public function progressAction(DataSet $dataSet): JsonResponse
    {
        $jobNamePrefix = sprintf('%s:%d:', ImportDataSetTopic::getName(), $dataSet->getId());

        /** @var JobEntity[] $rootJobs */
        $rootJobs = $this->doctrine
            ->getManagerForClass(JobEntity::class)
            ->getRepository(JobEntity::class)
            ->createQueryBuilder('j')
            ->where('j.rootJob IS NULL')
            ->andWhere('j.name LIKE :prefix')
            ->setParameter('prefix', $jobNamePrefix . '%')
            ->orderBy('j.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return new JsonResponse(array_map(static function (JobEntity $job): array {
            $data = $job->getData() ?? [];

            return [
                'id'        => $job->getId(),
                'status'    => $job->getStatus(),
                'progress'  => $job->getJobProgress(),
                'itemCount' => $data['itemCount'] ?? null,
                'fileName'  => $data['fileName'] ?? null,
                'startedAt' => $job->getStartedAt()?->format(\DateTimeInterface::ATOM),
                'stoppedAt' => $job->getStoppedAt()?->format(\DateTimeInterface::ATOM),
            ];
        }, $rootJobs));
    }
}
