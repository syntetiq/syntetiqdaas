<?php

namespace SyntetiQ\Bundle\ModelBundle\Model;

use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;

class ModelBuildConstants
{
    public const MODEL_ROOT_ENV = 'SQ_MODEL_ROOT';
    public const DEFAULT_MODEL_ROOT = '/data/var/data/model';
    const TENSORBOARD_PATH = '/tb/';

    public static function getModelRoot(): string
    {
        $modelRoot = getenv(self::MODEL_ROOT_ENV);
        if (!is_string($modelRoot) || trim($modelRoot) === '') {
            $modelRoot = self::DEFAULT_MODEL_ROOT;
        }

        return rtrim($modelRoot, '/');
    }

    public static function getBuildRootDir(): string
    {
        return self::getModelRoot() . '/workdir/builds';
    }

    public static function getTensorBoardBaseDir(): string
    {
        return self::getModelRoot() . '/tensorboard';
    }

    public static function getRunnerScriptPath(): string
    {
        return self::getModelRoot() . '/runner.sh';
    }

    public static function getDatasetQaRunnerScriptPath(): string
    {
        return self::getModelRoot() . '/dataset_qa_runner.sh';
    }

    public static function getBuildRelativeDir(ModelBuild $modelBuild): string
    {
        return sprintf(
            'workdir/builds/%d_%d/',
            $modelBuild->getModel()->getId(),
            $modelBuild->getId()
        );
    }

    public static function getBuildDir(ModelBuild $modelBuild): string
    {
        return sprintf(
            '%s/%d_%d/',
            self::getBuildRootDir(),
            $modelBuild->getModel()->getId(),
            $modelBuild->getId()
        );
    }

    public static function getTensorBoardDataDir(ModelBuild $modelBuild): string
    {
        return sprintf(
            '%s/%d_%d',
            self::getTensorBoardBaseDir(),
            $modelBuild->getModel()->getId(),
            $modelBuild->getId()
        );
    }

    public static function getTensorBoardRunName(ModelBuild $modelBuild): string
    {
        return sprintf(
            '%d_%d',
            $modelBuild->getModel()->getId(),
            $modelBuild->getId()
        );
    }

    public static function getTensorBoardUrl(ModelBuild $modelBuild): string
    {
        return sprintf(
            '%s#scalars&runFilter=%s',
            self::TENSORBOARD_PATH,
            rawurlencode(self::getTensorBoardRunName($modelBuild))
        );
    }
}
