<?php

namespace SyntetiQ\Bundle\DataSetBundle\Provider;

use League\Flysystem\FilesystemOperator;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\DataSetBundle\Async\Topic\ImportDataSetTopic;

class ImportDataSetProvider
{
    public function __construct(
        private MessageProducerInterface $producer,
        private FilesystemOperator $importTmpFilesystem
    ) {}

    public function handleLocalFile(string $filePath, string $originalName, DataSet $dataSet, $tag = null): void
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION) ?: pathinfo($filePath, PATHINFO_EXTENSION);

        $this->queueImportFile($filePath, $extension, $dataSet, $tag);
    }

    public function sendMessage(string $fileName, int $dataSetId, array $extraData = []): void
    {
        $body = [
            'fileName' => $fileName,
            'dataSetId' => $dataSetId,
            'requestId' => uniqid('req_', true),
        ];

        if (!empty($extraData)) {
            $body = array_merge($body, $extraData);
        }

        $this->producer->send(
            ImportDataSetTopic::getName(),
            $body
        );
    }

    private function queueImportFile(string $filePath, ?string $extension, DataSet $dataSet, $tag = null): void
    {
        $normalizedExtension = strtolower((string) $extension);
        $fileName = md5(uniqid('', true)) . '.' . ($normalizedExtension !== '' ? $normalizedExtension : 'zip');

        $stream = fopen($filePath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException(sprintf('Unable to open import file %s', $filePath));
        }

        try {
            $this->importTmpFilesystem->writeStream('import_tmp/' . $fileName, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $extraData = [];
        $tagValue = $tag;
        if ($tagValue !== null && $tagValue !== '') {
            $extraData['tag'] = $tagValue;
        }

        $this->sendMessage($fileName, $dataSet->getId(), $extraData);
    }
}
