<?php

namespace SyntetiQ\Bundle\ModelBundle\Provider;

class ArchiveProvider
{
    public function zipDirectory($zipFilePath, $sourceFolder) {
        // Create a new ZipArchive instance
        $zip = new \ZipArchive();

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            // Add all files and subdirectories from the source folder to the zip archive
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceFolder),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                // Skip directories (we only want files)
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($sourceFolder) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            // Close the zip archive
            $zip->close();

            echo 'Zip file created successfully: ' . $zipFilePath;
        } else {
            echo 'Failed to create zip file';
        }
    }
}
