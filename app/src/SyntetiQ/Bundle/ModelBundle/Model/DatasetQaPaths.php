<?php

namespace SyntetiQ\Bundle\ModelBundle\Model;

use SyntetiQ\Bundle\DataSetBundle\Entity\DataSet;
use SyntetiQ\Bundle\ModelBundle\Entity\ModelBuild;

final class DatasetQaPaths
{
    public const DATASET_QA_LOCAL_BASE_DIR = '/data/var/data/import_export/dataset_qa';

    public static function getBuildBaseStorageDir(ModelBuild $modelBuild): string
    {
        return sprintf('builds/%d_%d', $modelBuild->getModel()->getId(), $modelBuild->getId());
    }

    public static function getBuildLocalOutputDir(ModelBuild $modelBuild): string
    {
        return rtrim(ModelBuildConstants::getBuildDir($modelBuild), '/') . '/dataset_qa';
    }

    public static function getBuildStorageDir(ModelBuild $modelBuild): string
    {
        return self::getBuildBaseStorageDir($modelBuild) . '/dataset_qa';
    }

    public static function getBuildLogsStorageDir(ModelBuild $modelBuild): string
    {
        return self::getBuildBaseStorageDir($modelBuild) . '/logs';
    }

    public static function getBuildInputDir(ModelBuild $modelBuild): string
    {
        return rtrim(ModelBuildConstants::getBuildDir($modelBuild), '/') . '/dataset_qa_input';
    }

    public static function getBuildInputRelativePath(ModelBuild $modelBuild): string
    {
        return rtrim(ModelBuildConstants::getBuildRelativeDir($modelBuild), '/') . '/dataset_qa_input';
    }

    public static function getBuildStatusRelativePath(ModelBuild $modelBuild, string $fileName): string
    {
        return rtrim(ModelBuildConstants::getBuildRelativeDir($modelBuild), '/') . '/' . ltrim($fileName, '/');
    }

    public static function getDataSetStorageDir(DataSet $dataSet): string
    {
        return self::getDataSetStorageDirById((int) $dataSet->getId());
    }

    public static function getDataSetStorageDirById(int $dataSetId): string
    {
        return self::getDataSetBaseStorageDirById($dataSetId) . '/dataset_qa';
    }

    public static function getDataSetBaseStorageDir(DataSet $dataSet): string
    {
        return self::getDataSetBaseStorageDirById((int) $dataSet->getId());
    }

    public static function getDataSetBaseStorageDirById(int $dataSetId): string
    {
        return sprintf('datasets/%d', $dataSetId);
    }

    public static function getDataSetLocalBaseDir(DataSet $dataSet): string
    {
        return self::getDataSetLocalBaseDirById((int) $dataSet->getId());
    }

    public static function getDataSetLocalBaseDirById(int $dataSetId): string
    {
        return sprintf('%s/%d', self::DATASET_QA_LOCAL_BASE_DIR, $dataSetId);
    }

    public static function getDataSetTempInputDir(DataSet $dataSet): string
    {
        return self::getDataSetLocalBaseDir($dataSet) . '_input';
    }

    public static function getDataSetTempWorkDir(DataSet $dataSet): string
    {
        return self::getDataSetLocalBaseDir($dataSet) . '_work';
    }
}
