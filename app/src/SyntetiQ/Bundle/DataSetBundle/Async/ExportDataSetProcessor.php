<?php

namespace SyntetiQ\Bundle\DataSetBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Imagine\Image\ImagineInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
use Oro\Bundle\AttachmentBundle\Provider\ExternalUrlProvider;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Yaml\Yaml;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\ExportDataSetTopic;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetExport;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItemTag;
use SyntetiQ\Bundle\DataSetBundle\Entity\ItemObjectConfiguration;
use SyntetiQ\Bundle\DataSetBundle\Model\Group;
use SyntetiQ\Bundle\ModelBundle\Model\Yolo as YoloModel;
use SyntetiQ\Bundle\ModelBundle\Provider\ArchiveProvider;
use SyntetiQ\Bundle\DataSetBundle\Service\YoloDatasetMaterializer;

class ExportDataSetProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const EMAIL_TEMPLATE = 'export_data_set_finish_notification';

    public function __construct(
        private ManagerRegistry             $doctrine,
        private JobRunner                   $jobRunner,
        private FileManager                 $fileManager,
        private FileManager                 $fileManagerImportExport,
        private Serializer                  $serializer,
        private ImagineInterface            $imagine,
        private ImageResizeManagerInterface $imageResizeManager,
        private ArchiveProvider             $archiveProvider,
        private EmailNotificationManager    $emailNotificationManager,
        private ExternalUrlProvider         $fileUrlProvider,
        private YoloDatasetMaterializer     $yoloDatasetMaterializer
    ) {}

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [ExportDataSetTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();
        $dataSetExportId = $data['dataSetExportId'];
        $exportTags = $this->normalizeTags($data['exportTags'] ?? []);

        if (!$dataSetExportId) {
            return self::REJECT;
        }

        $dataSetExportEntity = $this->loadDataSetExportEntity($dataSetExportId);
        $this->saveStartedAt($dataSetExportEntity);


        $dataSetEntity = $this->loadDataSetEntity($dataSetExportEntity->getDataSetId());
        $folderName = sprintf(
            '%s_%d',
            $this->sanitizeFileName($dataSetEntity->getName() ?: 'dataset'),
            $dataSetExportEntity->getId()
        );
        // Folder to be zipped
        $sourceFolder = '/data/var/data/import_export/' . $folderName;
        // Zip file name
        $zipFilePath = '/data/var/data/import_export/' . $folderName . '.zip';

        try {
            $this->buildDataset($dataSetEntity, $folderName, $exportTags);
        } catch (\Exception $e) {
            return self::REJECT;
        }

        $this->archiveProvider->zipDirectory($zipFilePath, $sourceFolder);

        $resultFile = $this->fileManager->createFileEntity($zipFilePath);
        $modelBuildEM = $this->doctrine->getManager();
        $modelBuildEM->persist($resultFile);

        //        $file = $this->fileManager->getFile($fileName);


        $dataSetExportEntity->setResultFile($resultFile);

        $this->saveFinishedAt($dataSetExportEntity);

        //        $notification = new TemplateEmailNotification(
        //            new EmailTemplateCriteria(self::EMAIL_TEMPLATE, User::class),
        //            [$user],
        //            $user
        //        );
        //
        //        ;
        //
        //        $fileName = '/import_export/' . $folderName.'.zip';
        //        $file = $this->fileManager->getFile($fileName);
        //        $link = $this->fileUrlProvider
        //            ->getFileUrl($file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD);
        //
        //        $this->emailNotificationManager->processSingle($notification, ['link'=> $link ]);

        if (is_dir($sourceFolder)) {
            $this->deleteDirectory($sourceFolder);
            if (file_exists($zipFilePath)) {
                unlink($zipFilePath);
            }
        }

        $result = $this->jobRunner->runUniqueByMessage(
            $message,

            function (JobRunner $jobRunner, Job $job) use ($dataSetEntity) {

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    protected function loadDataSetEntity(int $dataSetId)
    {
        $dataSetRepository = $this->doctrine->getRepository(DataSet::class);

        return $dataSetRepository->find($dataSetId);
    }

    protected function loadDataSetExportEntity(int $dataSetExportId)
    {
        $dataSetExportRepository = $this->doctrine->getRepository(DataSetExport::class);

        return $dataSetExportRepository->find($dataSetExportId);
    }

    protected function buildDataset(DataSet $dataset, $sourceFolder, array $exportTags = []): array
    {
        $datasetFolder = '../import_export/' . $sourceFolder;
        return $this->yoloDatasetMaterializer->materialize(
            $this->loadItemsForExport($dataset, $exportTags),
            $this->fileManagerImportExport,
            $datasetFolder,
            true,
            [],
            true
        );
    }

    /**
     * @return DataSetItem[]
     */
    private function loadItemsForExport(DataSet $dataset, array $exportTags): array
    {
        $queryBuilder = $this->doctrine
            ->getRepository(DataSetItem::class)
            ->createQueryBuilder('item')
            ->where('item.dataSet = :dataSet')
            ->setParameter('dataSet', $dataset)
            ->orderBy('item.id', 'ASC');

        if ($exportTags !== []) {
            $queryBuilder
                ->andWhere(
                    'EXISTS (
                        SELECT exportTagFilter.id
                        FROM ' . DataSetItemTag::class . ' exportTagFilter
                        WHERE exportTagFilter.dataSetItem = item
                            AND exportTagFilter.name IN (:exportTags)
                    )'
                )
                ->setParameter('exportTags', $exportTags);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    private function resolveGroup(DataSetItem $item): string
    {
        return match ($item->getGroup()) {
            Group::TEST => Group::TEST,
            Group::VAL, 'validation' => Group::VAL,
            Group::TRAIN, null, '' => Group::TRAIN,
            default => Group::TRAIN,
        };
    }

    private function sanitizeFileName(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'dataset';
    }

    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        // Open the directory
        $handle = opendir($dir);

        // Loop through the directory
        while (($file = readdir($handle)) !== false) {
            if ($file != '.' && $file != '..') {
                $filePath = $dir . '/' . $file;

                // If it's a directory, recursively delete it
                if (is_dir($filePath)) {
                    $this->deleteDirectory($filePath);
                } else {
                    // If it's a file, delete it
                    unlink($filePath);
                }
            }
        }

        // Close the directory handle
        closedir($handle);

        // Remove the empty directory
        rmdir($dir);

        return true;
    }

    /**
     * @param DataSetExport $dataSetExportEntity
     * @return void
     * @throws \Exception
     */
    public function saveStartedAt(DataSetExport $dataSetExportEntity): void
    {
        $dataSetExportEntity->setStartedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $em = $this->doctrine->getManagerForClass(DataSetExport::class);
        $em->persist($dataSetExportEntity);
        $em->flush();
    }

    /**
     * @param DataSetExport $dataSetExportEntity
     * @return void
     * @throws \Exception
     */
    public function saveFinishedAt(DataSetExport $dataSetExportEntity): void
    {
        $dataSetExportEntity->setFinishedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $em = $this->doctrine->getManagerForClass(DataSetExport::class);
        $em->persist($dataSetExportEntity);
        $em->flush();
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
