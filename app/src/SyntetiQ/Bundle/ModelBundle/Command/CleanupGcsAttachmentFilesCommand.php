<?php

namespace SyntetiQ\Bundle\ModelBundle\Command;

use Gaufrette\Filesystem;
use Oro\Bundle\AttachmentBundle\Command\CleanupAttachmentFilesCommand;
use Oro\Bundle\GaufretteBundle\FileManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'syntetiq:attachment:cleanup')]
class CleanupGcsAttachmentFilesCommand extends CleanupAttachmentFilesCommand
{
    private Filesystem $attachmentsFilesystem;

    public function __construct(
        int $collectAttachmentFilesBatchSize,
        int $loadAttachmentsBatchSize,
        int $loadExistingAttachmentsBatchSize,
        FileManager $dataFileManager,
        ManagerRegistry $doctrine,
        FileManager $attachmentFileManager,
        Filesystem $attachmentsFilesystem
    ) {
        parent::__construct(
            $collectAttachmentFilesBatchSize,
            $loadAttachmentsBatchSize,
            $loadExistingAttachmentsBatchSize,
            $dataFileManager,
            $doctrine,
            $attachmentFileManager
        );

        $this->attachmentsFilesystem = $attachmentsFilesystem;
    }

    #[\Override]
    protected function getAttachmentFileNames(): iterable
    {
        $keys = $this->attachmentsFilesystem->keys();

        foreach ($keys as $key) {
            if ($key === '' || str_ends_with($key, '/')) {
                continue;
            }

            yield $key;
        }
    }
}
