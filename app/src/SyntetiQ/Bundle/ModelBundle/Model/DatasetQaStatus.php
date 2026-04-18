<?php

namespace SyntetiQ\Bundle\ModelBundle\Model;

final class DatasetQaStatus
{
    public const IDLE = 'idle';
    public const QUEUED = 'queued';
    public const RUNNING = 'running';
    public const SUCCEEDED = 'succeeded';
    public const FAILED = 'failed';

    public static function values(): array
    {
        return [
            self::IDLE,
            self::QUEUED,
            self::RUNNING,
            self::SUCCEEDED,
            self::FAILED,
        ];
    }
}
