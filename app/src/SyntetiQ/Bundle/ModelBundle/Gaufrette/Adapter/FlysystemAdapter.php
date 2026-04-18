<?php

namespace SyntetiQ\Bundle\ModelBundle\Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Adapter\ListKeysAware;
use Gaufrette\Adapter\MetadataSupporter;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Throwable;

class FlysystemAdapter implements Adapter, ListKeysAware, MetadataSupporter
{
    private string $prefix;

    /**
     * @var array<string, array>
     */
    private array $metadata = [];

    public function __construct(private FilesystemOperator $filesystem, string $prefix = '')
    {
        $this->prefix = trim($prefix, '/');
    }

    public function read($key)
    {
        try {
            return $this->filesystem->read($this->applyPrefix($key));
        } catch (Throwable $exception) {
            return false;
        }
    }

    public function write($key, $content)
    {
        $path = $this->applyPrefix($key);
        $config = [];

        if (isset($this->metadata[$path]) && !empty($this->metadata[$path])) {
            $config['metadata'] = $this->metadata[$path];
        }

        try {
            $this->filesystem->write($path, $content, $config);
        } catch (Throwable $exception) {
            return false;
        }

        return is_string($content) ? strlen($content) : 0;
    }

    public function exists($key)
    {
        try {
            return $this->filesystem->fileExists($this->applyPrefix($key));
        } catch (FilesystemException $exception) {
            return false;
        }
    }

    public function keys()
    {
        return $this->collectKeys();
    }

    public function listKeys($prefix = '')
    {
        $result = [
            'keys' => [],
            'dirs' => [],
        ];

        foreach ($this->filesystem->listContents($this->prefix, true) as $attributes) {
            if (!$this->isWithinPrefix($attributes)) {
                continue;
            }

            $relativePath = $this->stripPrefix($attributes->path());

            if ($prefix !== '' && strpos($relativePath, $prefix) !== 0) {
                continue;
            }

            if ($attributes->isDir()) {
                $result['dirs'][] = $relativePath;
            } else {
                $result['keys'][] = $relativePath;
            }
        }

        return $result;
    }

    public function mtime($key)
    {
        try {
            return $this->filesystem->lastModified($this->applyPrefix($key));
        } catch (FilesystemException $exception) {
            return false;
        }
    }

    public function delete($key)
    {
        try {
            $this->filesystem->delete($this->applyPrefix($key));

            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public function rename($sourceKey, $targetKey)
    {
        try {
            $this->filesystem->move(
                $this->applyPrefix($sourceKey),
                $this->applyPrefix($targetKey)
            );

            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public function isDirectory($key)
    {
        try {
            return $this->filesystem->directoryExists($this->applyPrefix($key));
        } catch (FilesystemException $exception) {
            return false;
        }
    }

    public function setMetadata($key, $content)
    {
        $this->metadata[$this->applyPrefix($key)] = $content;
    }

    public function getMetadata($key)
    {
        return $this->metadata[$this->applyPrefix($key)] ?? [];
    }

    private function collectKeys(): array
    {
        $keys = [];

        foreach ($this->filesystem->listContents($this->prefix, true) as $attributes) {
            if (!$attributes->isFile() || !$this->isWithinPrefix($attributes)) {
                continue;
            }

            $keys[] = $this->stripPrefix($attributes->path());
        }

        sort($keys);

        return $keys;
    }

    private function applyPrefix(string $key): string
    {
        $key = ltrim($key, '/');

        if ($this->prefix === '') {
            return $key;
        }

        return $this->prefix . '/' . $key;
    }

    private function stripPrefix(string $path): string
    {
        if ($this->prefix === '') {
            return $path;
        }

        $needle = $this->prefix . '/';
        if (strpos($path, $needle) === 0) {
            return substr($path, strlen($needle));
        }

        return $path;
    }

    private function isWithinPrefix(StorageAttributes $attributes): bool
    {
        if ($this->prefix === '') {
            return true;
        }

        $path = $attributes->path();

        if ($path === $this->prefix) {
            return false;
        }

        return strpos($path, $this->prefix . '/') === 0;
    }
}
