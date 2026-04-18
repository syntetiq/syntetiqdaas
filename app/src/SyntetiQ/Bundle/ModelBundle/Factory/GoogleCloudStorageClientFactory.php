<?php

namespace SyntetiQ\Bundle\ModelBundle\Factory;

use Google\Cloud\Storage\StorageClient;
use RuntimeException;

class GoogleCloudStorageClientFactory
{
    public static function create(?string $projectId, ?string $keyFilePath, ?string $storageEmulatorHost): StorageClient
    {
        if (!class_exists(StorageClient::class)) {
            throw new RuntimeException('Package "google/cloud-storage" is required to use the Flysystem GCS adapter.');
        }

        $config = [];

        if (!empty($projectId)) {
            $config['projectId'] = $projectId;
        }

        if (!empty($keyFilePath)) {
            if (!is_file($keyFilePath)) {
                throw new RuntimeException(sprintf('GCS key file "%s" does not exist.', $keyFilePath));
            }

            if (!is_readable($keyFilePath)) {
                throw new RuntimeException(sprintf('GCS key file "%s" is not readable.', $keyFilePath));
            }

            $config['keyFilePath'] = $keyFilePath;
        }

        if (!empty($storageEmulatorHost)) {
            $endpoint = rtrim($storageEmulatorHost, '/');
            putenv('STORAGE_EMULATOR_HOST=' . $endpoint);
            $config['apiEndpoint'] = $endpoint;
        }

        return new StorageClient($config);
    }
}
