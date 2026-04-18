<?php

namespace SyntetiQ\Bundle\DataSetBundle\Service;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use SyntetiQ\Bundle\DataSetBundle\Entity\DataSetItem;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DataSetItemSamSegmentationService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private FileManager $fileManager,
        private string $baseUrl,
        private int $timeoutSeconds,
    ) {
    }

    public function segmentClick(DataSetItem $dataSetItem, float $xPct, float $yPct): array
    {
        $image = $dataSetItem->getImage();
        if (!$image || !$image->getFilename()) {
            throw new \InvalidArgumentException('The dataset item does not have an image to segment.');
        }

        $imageContent = $this->fileManager->getContent($image, false);
        if ($imageContent === null || $imageContent === '') {
            throw new \RuntimeException('Could not load the dataset item image content.');
        }

        $responsePayload = $this->requestSegmentation(
            [
                'image_id' => sprintf('dataset-item-%d:%s', $dataSetItem->getId() ?? 0, $image->getFilename()),
                'image_base64' => base64_encode($imageContent),
                'image_mime_type' => $image->getMimeType() ?: 'application/octet-stream',
                'point' => [
                    'x' => $xPct,
                    'y' => $yPct,
                ],
            ]
        );

        return [
            'bbox' => $this->normalizeBbox($responsePayload['bbox'] ?? null),
            'score' => isset($responsePayload['score']) ? (float) $responsePayload['score'] : null,
        ];
    }

    /**
     * @throws ExceptionInterface
     */
    private function requestSegmentation(array $payload): array
    {
        $response = $this->httpClient->request(
            'POST',
            rtrim($this->baseUrl, '/') . '/segment/click',
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => $this->timeoutSeconds,
            ]
        );

        $statusCode = $response->getStatusCode();
        $responsePayload = $response->toArray(false);
        if ($statusCode < 200 || $statusCode >= 300) {
            $message = is_array($responsePayload) && isset($responsePayload['detail']) && is_string($responsePayload['detail'])
                ? $responsePayload['detail']
                : sprintf('SAM service returned HTTP %d.', $statusCode);

            throw new \RuntimeException($message);
        }

        if (!is_array($responsePayload)) {
            throw new \RuntimeException('SAM service returned an invalid response payload.');
        }

        return $responsePayload;
    }

    private function normalizeBbox(mixed $bbox): array
    {
        if (!is_array($bbox)) {
            throw new \RuntimeException('SAM service response is missing bbox data.');
        }

        $x = $this->normalizeBoundValue($bbox['x'] ?? null, 'bbox.x');
        $y = $this->normalizeBoundValue($bbox['y'] ?? null, 'bbox.y');
        $width = $this->normalizeBoundValue($bbox['width'] ?? null, 'bbox.width');
        $height = $this->normalizeBoundValue($bbox['height'] ?? null, 'bbox.height');

        $x = max(0.0, min(100.0, $x));
        $y = max(0.0, min(100.0, $y));
        $width = max(0.0, min(100.0 - $x, $width));
        $height = max(0.0, min(100.0 - $y, $height));

        if ($width <= 0.0 || $height <= 0.0) {
            throw new \RuntimeException('SAM service returned a zero-sized bounding box.');
        }

        return [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
        ];
    }

    private function normalizeBoundValue(mixed $value, string $field): float
    {
        if (!is_numeric($value)) {
            throw new \RuntimeException(sprintf('SAM service returned an invalid %s value.', $field));
        }

        return (float) $value;
    }
}
